# AI Code Detector - Open Source YAML Rules

> Portable, maintainable AI code detection rules for identifying AI-generated pull requests and commits. Detect Claude Code, GitHub Copilot, Cursor, and other AI coding assistants.

[![Validate rules](https://github.com/coderbuds/ai-detector/actions/workflows/validate.yml/badge.svg)](https://github.com/coderbuds/ai-detector/actions/workflows/validate.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Stars](https://img.shields.io/github/stars/coderbuds/ai-detector?style=social)](https://github.com/coderbuds/ai-detector/stargazers)

---

## 📖 Why We Open-Sourced This

Over 46% of code on GitHub is now AI-assisted (GitHub Octoverse 2024). Engineering teams need transparency into which pull requests use AI coding tools.

We built these detection rules for [Coderbuds](https://coderbuds.com) and decided to **open-source them** because:

- ✅ **Transparency builds trust** - Developers deserve to know how AI detection works
- ✅ **Community contributions** - Help us keep rules updated as AI tools evolve
- ✅ **Framework-agnostic** - Use with any language (PHP, Python, Node.js, Ruby, Go)
- ✅ **No vendor lock-in** - Own the detection logic, integrate however you want

**📝 Read the full story:** [Why We Open-Sourced Our AI Detection Rules](https://coderbuds.com/blog/open-source-ai-code-detection-yaml-rules)

---

## 🚀 Quick Start

All detection rules are in the `rules/` directory as YAML files:

```bash
git clone https://github.com/coderbuds/ai-detector
cd ai-detector
```

**Example Rule:**
```yaml
# rules/claude-code.yml
tool:
  id: claude-code
  name: Claude Code
  provider: Anthropic

explicit_markers:
  commit_footers:
    - pattern: '\[Claude Code\]\(https://claude\.com/claude-code\)'
      regex: true
      confidence: 100
      description: "Official Claude Code footer in PR description"

  co_author_attributions:
    - pattern: 'Co-Authored-By: Claude Sonnet'
      regex: false
      confidence: 100
      description: "Claude co-author attribution"
```

---

## 🤖 Supported AI Tools

| Tool | Provider | What it leaves behind |
|------|----------|------------------------|
| **Claude Code** | Anthropic | Description footer, `Co-Authored-By: Claude` trailer, `claude/` branches |
| **GitHub Copilot** | Microsoft | Bot commit authors, co-author trailer, footer |
| **Cursor** | Anysphere | Footer and links, `CURSOR_SUMMARY` comment, `cursor/` branches |
| **Jules** | Google | "PR created automatically by Jules" signature, task link, `jules-<id>` branches |
| **Devin** | Cognition AI | Bot commit authors, footer, links |
| **OpenAI Codex** | OpenAI | `codex/` branches, `[codex]` titles, Codex task links, `codex` label |
| **WindSurf** | Codeium | Footer link, HTML comment, `windsurf/` branches |
| **Aider** | Open Source | `aider: ` commit prefix, aider.chat links, `aider/` branches |
| **v0.dev** | Vercel | Footer and HTML comments |
| **Replit AI** | Replit | Bot commit author, footer link, HTML comment, `replit-ai` label |

**Missing a tool?** [Submit a PR](https://github.com/coderbuds/ai-detector/pulls) or [open an issue](https://github.com/coderbuds/ai-detector/issues).

---

## 📦 Usage Examples

### PHP (Laravel/Symfony)

```php
use Symfony\Component\Yaml\Yaml;

// Load all rule files
$rulesPath = __DIR__ . '/vendor/coderbuds/ai-detector/rules';
$rules = [];

foreach (glob($rulesPath . '/*.yml') as $file) {
    $data = Yaml::parseFile($file);
    $rules[$data['tool']['id']] = $data;
}

// Check PR for AI markers
function detectAI(string $prDescription, array $commits): ?array
{
    global $rules;

    foreach ($rules as $toolId => $rule) {
        // Check commit footers
        foreach ($rule['explicit_markers']['commit_footers'] ?? [] as $marker) {
            $pattern = $marker['regex']
                ? '#' . $marker['pattern'] . '#i'
                : '/' . preg_quote($marker['pattern'], '/') . '/i';

            if (preg_match($pattern, $prDescription)) {
                return [
                    'tool' => $rule['tool']['name'],
                    'confidence' => $marker['confidence'],
                    'indicator' => $marker['description'],
                ];
            }
        }

        // Check bot authors in commits
        foreach ($rule['explicit_markers']['bot_authors'] ?? [] as $bot) {
            foreach ($commits as $commit) {
                if (isset($bot['email']) && $commit['author']['email'] === $bot['email']) {
                    return [
                        'tool' => $rule['tool']['name'],
                        'confidence' => $bot['confidence'],
                        'indicator' => $bot['description'],
                    ];
                }
            }
        }
    }

    return null; // No AI detected
}
```

### Python

```python
import yaml
import re
from pathlib import Path

# Load all rules
rules = {}
rules_dir = Path('vendor/coderbuds/ai-detector/rules')

for rule_file in rules_dir.glob('*.yml'):
    with open(rule_file) as f:
        data = yaml.safe_load(f)
        rules[data['tool']['id']] = data

def detect_ai(pr_description, commits):
    """Detect AI tool usage in pull request."""
    for tool_id, rule in rules.items():
        # Check commit footers
        for marker in rule.get('explicit_markers', {}).get('commit_footers', []):
            pattern = marker['pattern'] if marker.get('regex') else re.escape(marker['pattern'])
            if re.search(pattern, pr_description, re.IGNORECASE):
                return {
                    'tool': rule['tool']['name'],
                    'confidence': marker['confidence'],
                    'indicator': marker['description']
                }

        # Check bot authors
        for bot in rule.get('explicit_markers', {}).get('bot_authors', []):
            for commit in commits:
                if bot.get('email') and commit['author']['email'] == bot['email']:
                    return {
                        'tool': rule['tool']['name'],
                        'confidence': bot['confidence'],
                        'indicator': bot['description']
                    }

    return None  # No AI detected
```

### Node.js / TypeScript

```javascript
const yaml = require('js-yaml');
const fs = require('fs');
const path = require('path');

// Load all rules
const rulesDir = path.join(__dirname, 'node_modules/@coderbuds/ai-detector/rules');
const rules = {};

fs.readdirSync(rulesDir)
  .filter(file => file.endsWith('.yml'))
  .forEach(file => {
    const data = yaml.load(fs.readFileSync(path.join(rulesDir, file), 'utf8'));
    rules[data.tool.id] = data;
  });

function detectAI(prDescription, commits) {
  for (const [toolId, rule] of Object.entries(rules)) {
    // Check commit footers
    for (const marker of rule.explicit_markers?.commit_footers || []) {
      const pattern = new RegExp(marker.pattern, 'i');
      if (pattern.test(prDescription)) {
        return {
          tool: rule.tool.name,
          confidence: marker.confidence,
          indicator: marker.description
        };
      }
    }

    // Check bot authors
    for (const bot of rule.explicit_markers?.bot_authors || []) {
      for (const commit of commits) {
        if (bot.email && commit.author.email === bot.email) {
          return {
            tool: rule.tool.name,
            confidence: bot.confidence,
            indicator: bot.description
          };
        }
      }
    }
  }

  return null; // No AI detected
}
```

---

## 📋 Detection Categories

The YAML rules check for these marker types:

| Category | Description | Example |
|----------|-------------|---------|
| `commit_footers` | Signatures in PR descriptions | "🤖 Generated with Claude Code" |
| `co_author_attributions` | Co-author tags in commits | `Co-Authored-By: GitHub Copilot` |
| `bot_authors` | Bot emails and usernames | `github-copilot[bot]`, `noreply@anthropic.com` |
| `html_comments` | Special HTML comments | `<!-- Generated by AI -->` |
| `labels` | PR labels | `codex`, `ai-generated` |
| `branch_patterns` | Branch naming conventions | `codex/feature`, `claude/fix-login` |
| `text_patterns` | Title or description text (`location: title` or `description`) | `[codex] Fix publishing` |

---

## 🎯 What This Detects (And Doesn't)

### ✅ **Detects (Explicit Attribution)**

- Pull requests with AI tool footers
- Commits authored by AI bots
- Co-author attributions to AI tools
- Branch names following AI tool patterns
- PR labels indicating AI usage

A marker at confidence 100 is the tool signing its own work. Lower confidences — branch names, title prefixes — are strong conventions, not signatures: a person can name a branch `claude/…` too.

### ❌ **Doesn't Detect (Without Additional Analysis)**

- Subtle AI usage without markers
- ChatGPT code copied manually
- AI-assisted refactoring without attribution
- Code quality or "AI-like" patterns
- Agents that sign nothing. **Codex CLI run locally leaves no marker at all**, and neither does any agent whose footer a person deletes

**No match does not mean a person wrote it.** On Coderbuds' own production data, pull requests with no marker were routinely agent-written — including every one Codex CLI produced. Treat "no marker found" as unknown, never as human. The only reliable source for those is the agent saying so itself; Coderbuds asks coding agents to report their authorship over MCP for exactly this reason.

**For behavioral analysis** (analyzing code patterns), see [Coderbuds Platform](https://coderbuds.com).

---

## 🆚 Free vs Paid

This package is **100% free and open source** (MIT License). Use it for:

- ✅ Individual PR detection
- ✅ CI/CD pipeline checks
- ✅ Local development workflows
- ✅ Custom integrations

**[Coderbuds Platform](https://coderbuds.com)** (paid service) adds:

- 📊 Team-level analytics over time
- 📈 AI adoption trends and insights
- 🎯 Correlation with DORA metrics
- 🔍 Behavioral AI detection (no explicit markers needed)
- 🏢 Enterprise features (SSO, audit logs)
- 📝 Custom reporting and exports

**Analogy:** This package is like Sentry's SDK (free). Coderbuds is like Sentry's hosted platform (paid).

---

## 🤝 Contributing

We welcome contributions! Help us:

- 🆕 Add new AI tool signatures
- 🐛 Fix detection edge cases
- 📖 Improve documentation
- 🧪 Add test cases

### How to Contribute

1. **Fork the repository**
2. **Create a new rule file** `rules/your-tool.yml`
3. **Follow the schema:**

```yaml
tool:
  id: your-tool-slug
  name: Your Tool Name
  provider: Company Name
  website: https://tool-website.com

explicit_markers:
  commit_footers:
    - pattern: 'Generated with Your Tool'
      regex: false
      confidence: 100
      description: "Tool footer in PR description"

  bot_authors:
    - username: 'your-tool[bot]'
      confidence: 100
      description: "Your Tool bot author"
```

`bot_authors` entries match on `username`, `email`, `email_pattern` or `name_pattern`; `labels` entries on `name`. Any other key is silently ignored by the matcher, so the validator rejects it.

4. **Add fixture cases** to `fixtures/cases.yml` — a pull request your rule must catch, and one it must not
5. **Run the validator:** `pip install pyyaml && python3 scripts/validate_rules.py`
6. **Submit a PR** — CI runs the same check

### Contribution Guidelines

- Include at least 3 example PRs showing the pattern
- Match signatures, not mentions: a rule that fires on a tool's *name* also fires on every changelog and dependency bump that mentions it
- Document confidence levels (100 = definitive, 80+ = high, 60+ = medium)
- Add test cases if possible
- Update this README's tool table

---

## 📚 Documentation

- **[Blog Post: Why We Open-Sourced This](https://coderbuds.com/blog/open-source-ai-code-detection-yaml-rules)**
- **[Coderbuds Platform](https://coderbuds.com)** - Team analytics
- **[GitHub Discussions](https://github.com/coderbuds/ai-detector/discussions)** - Ask questions
- **[Issues](https://github.com/coderbuds/ai-detector/issues)** - Report bugs

---

## 📜 License

**MIT License** - Use freely in commercial and open-source projects.

See [LICENSE.md](LICENSE.md) for details.

---

## 🙏 Credits

Created by **[Coderbuds](https://coderbuds.com)** - AI adoption analytics for engineering teams.

**Built with transparency in mind.** Developers deserve to know how AI detection works.

**Star this repo** ⭐ if you find it useful!

---

## 🔗 Links

- **[Try the Live Detector](https://coderbuds.com/blog/open-source-ai-code-detection-yaml-rules#try-it)** - Paste any GitHub PR URL
- **[Full Blog Post](https://coderbuds.com/blog/open-source-ai-code-detection-yaml-rules)** - Why we open-sourced this
- **[Coderbuds Platform](https://coderbuds.com)** - Team AI adoption analytics
- **[GitHub](https://github.com/coderbuds/ai-detector)** - Source code
- **[Issues](https://github.com/coderbuds/ai-detector/issues)** - Bug reports
- **[Discussions](https://github.com/coderbuds/ai-detector/discussions)** - Community

---

**Have questions?** Open a [GitHub Discussion](https://github.com/coderbuds/ai-detector/discussions) or [tweet at us](https://twitter.com/coderbuds).

**Want team insights?** [Start tracking with Coderbuds](https://coderbuds.com/register) (30-day free trial, no credit card required).
