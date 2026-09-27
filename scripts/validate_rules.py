#!/usr/bin/env python3
"""Validate the detection rules and run them against fixture pull requests.

Two kinds of failure this exists to catch, both of which shipped before:

1. Rules that can never match. A key the matcher does not read (`pattern`
   under `bot_authors`, `pattern` under `labels`) or a regex written without
   `regex: true` is silently compared as literal text, forever.
2. Rules that match the wrong thing. Every fixture in fixtures/cases.yml is a
   pull request shape seen in production, with the tool it must — or must
   not — be attributed to.

The reference matcher mirrors Coderbuds' CheckYamlRulesAction: rule files are
checked in filename order and the first tool with any matching marker wins.
Patterns are PCRE in production; everything here also compiles under Python's
`re`, and the validator rejects `#` because production uses it as the regex
delimiter.

Usage: python3 scripts/validate_rules.py
"""

import re
import sys
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parent.parent
RULES_DIR = ROOT / "rules"
CASES_FILE = ROOT / "fixtures" / "cases.yml"

PATTERN_CATEGORIES = {
    "commit_footers",
    "co_author_attributions",
    "html_comments",
    "text_patterns",
    "branch_patterns",
}
PATTERN_KEYS = {"pattern", "regex", "case_insensitive", "confidence", "description"}
LABEL_KEYS = {"name", "confidence", "description"}
BOT_MATCH_KEYS = {"username", "email", "email_pattern", "name_pattern"}
BOT_KEYS = BOT_MATCH_KEYS | {"regex", "case_insensitive", "confidence", "description"}
CLIENT_MATCH_KEYS = {"client_name", "user_agent"}
CLIENT_KEYS = CLIENT_MATCH_KEYS | {"case_insensitive", "confidence", "description"}
TOOL_KEYS = {"id", "name", "provider", "website", "variants"}
REGEX_HINT = re.compile(r"(^\^)|(\\[sdwbS.\[\]()])|(\[[^\]]+\])|(\.\*)")


def validate(path: Path, data: dict) -> list[str]:
    errors = []
    where = path.name

    tool = data.get("tool") or {}
    if not tool.get("id") or not tool.get("name"):
        errors.append(f"{where}: tool.id and tool.name are required")
    for key in set(tool) - TOOL_KEYS:
        errors.append(f"{where}: unknown tool key `{key}`")

    for category, markers in (data.get("explicit_markers") or {}).items():
        if category in PATTERN_CATEGORIES:
            allowed, required = PATTERN_KEYS | ({"location"} if category == "text_patterns" else set()), {"pattern"}
        elif category == "labels":
            allowed, required = LABEL_KEYS, {"name"}
        elif category == "bot_authors":
            allowed, required = BOT_KEYS, None
        elif category == "mcp_clients":
            allowed, required = CLIENT_KEYS, None
        else:
            errors.append(f"{where}: unknown marker category `{category}` is never checked")
            continue

        for index, marker in enumerate(markers or []):
            at = f"{where} {category}[{index}]"

            for key in set(marker) - allowed:
                errors.append(f"{at}: key `{key}` is never read by the matcher")

            if required and not required <= set(marker):
                errors.append(f"{at}: missing {sorted(required - set(marker))}")

            if category == "bot_authors" and not BOT_MATCH_KEYS & set(marker):
                errors.append(f"{at}: needs one of {sorted(BOT_MATCH_KEYS)}")

            if category == "mcp_clients" and not CLIENT_MATCH_KEYS & set(marker):
                errors.append(f"{at}: needs one of {sorted(CLIENT_MATCH_KEYS)}")

            if category == "text_patterns" and marker.get("location", "description") not in ("title", "description"):
                errors.append(f"{at}: location must be title or description")

            confidence = marker.get("confidence")
            if not isinstance(confidence, int) or not 0 < confidence <= 100:
                errors.append(f"{at}: confidence must be an integer from 1 to 100")

            for key in ("pattern", "email_pattern", "name_pattern", "client_name", "user_agent"):
                pattern = marker.get(key)
                if pattern is None:
                    continue

                is_regex = key != "pattern" or marker.get("regex", False)

                if is_regex:
                    if "#" in pattern:
                        errors.append(f"{at}: `#` is the production regex delimiter and cannot appear in a pattern")
                    try:
                        re.compile(pattern)
                    except re.error as error:
                        errors.append(f"{at}: invalid regex {pattern!r}: {error}")
                elif REGEX_HINT.search(pattern):
                    errors.append(f"{at}: {pattern!r} looks like a regex but has no `regex: true`, so it is matched as literal text")

    return errors


def text_matches(text: str | None, marker: dict, key: str = "pattern", force_regex: bool = False) -> bool:
    pattern = marker.get(key)

    if not text or not pattern:
        return False

    insensitive = marker.get("case_insensitive", False)

    if force_regex or marker.get("regex", False):
        return re.search(pattern, text, re.IGNORECASE if insensitive else 0) is not None

    return pattern.lower() in text.lower() if insensitive else pattern in text


def bot_matches(commits: list[dict], marker: dict) -> bool:
    for commit in commits:
        author = commit.get("author") or {}
        username = (author.get("username") or "").lower()
        email = (author.get("email") or "").lower()
        name = author.get("name") or ""

        if username and "username" in marker and marker["username"].lower() in username:
            return True
        if "email" in marker and marker["email"].lower() == email:
            return True
        if "email_pattern" in marker and text_matches(email, marker, "email_pattern", force_regex=True):
            return True
        if "name_pattern" in marker and text_matches(name, marker, "name_pattern", force_regex=True):
            return True

    return False


def client_matches(clients: list[dict], marker: dict) -> bool:
    for client in clients:
        if "client_name" in marker and text_matches(client.get("client_name"), marker, "client_name", force_regex=True):
            return True
        if "user_agent" in marker and text_matches(client.get("user_agent"), marker, "user_agent", force_regex=True):
            return True

    return False


def detect(rules: list[dict], pr: dict) -> str | None:
    title = pr.get("title", "")
    description = pr.get("description", "")
    commits = pr.get("commits", [])
    messages = [commit.get("message", "") for commit in commits]
    labels = [label.lower() for label in pr.get("labels", [])]
    clients = pr.get("mcp_clients", [])
    branch = pr.get("branch")

    if not branch:
        bracketed = re.match(r"^\[([^\]]+)\]", title)
        branch = bracketed.group(1) if bracketed else None

    for rule in rules:
        for category, markers in (rule.get("explicit_markers") or {}).items():
            for marker in markers or []:
                if category in ("commit_footers", "co_author_attributions", "html_comments"):
                    hit = any(text_matches(text, marker) for text in [description, *messages])
                elif category == "text_patterns":
                    hit = text_matches(title if marker.get("location") == "title" else description, marker)
                elif category == "branch_patterns":
                    hit = text_matches(branch, marker)
                elif category == "labels":
                    hit = bool(marker.get("name")) and marker["name"].lower() in labels
                elif category == "bot_authors":
                    hit = bot_matches(commits, marker)
                elif category == "mcp_clients":
                    hit = client_matches(clients, marker)
                else:
                    hit = False

                if hit:
                    return rule["tool"]["id"]

    return None


def main() -> int:
    errors = []
    rules = []

    for path in sorted(RULES_DIR.glob("*.yml")):
        data = yaml.safe_load(path.read_text())
        errors.extend(validate(path, data))
        rules.append(data)

    ids = [rule["tool"]["id"] for rule in rules if rule.get("tool")]
    for duplicate in {tool_id for tool_id in ids if ids.count(tool_id) > 1}:
        errors.append(f"duplicate tool.id `{duplicate}`")

    cases = yaml.safe_load(CASES_FILE.read_text())
    for case in cases:
        detected = detect(rules, case["pr"])
        if detected != case["expect"]:
            errors.append(f"case `{case['name']}`: expected {case['expect']!r}, detected {detected!r}")

    for error in errors:
        print(f"✗ {error}")

    if errors:
        return 1

    print(f"✓ {len(rules)} rule files valid, {len(cases)} fixture cases pass")
    return 0


if __name__ == "__main__":
    sys.exit(main())
