# AI Code Detector - YAML Rules

> Portable, maintainable AI code detection rules for identifying AI-generated pull requests and commits.

[![Accuracy](https://img.shields.io/badge/accuracy-98%25-brightgreen)](https://github.com/coderbuds/ai-detector)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

## Overview

This package provides **YAML-based detection rules** for identifying AI-generated code and pull requests. The rules detect explicit markers from popular AI coding tools.

## Supported Tools

- 🤖 **Claude Code** (Anthropic)
- 🔵 **GitHub Copilot** (Microsoft)
- ⚡ **Cursor** (Anysphere)
- 🟢 **OpenAI Codex** (OpenAI)
- 🚀 **Devin** (Cognition AI)

## Features

✅ **Portable** - Use with any language or platform
✅ **Maintainable** - Update rules without code changes  
✅ **Transparent** - Human-readable YAML format
✅ **Accurate** - 98% accuracy on real-world data

## Quick Start

All detection rules are in the `rules/` directory as YAML files.

**Example:**
```yaml
# rules/claude-code.yml
tool:
  id: claude-code
  name: Claude Code
  provider: Anthropic

explicit_markers:
  commit_footers:
    - pattern: '🤖\s*Generated with.*Claude Code'
      regex: true
      confidence: 100
      description: "Official Claude Code footer"
```

## Usage Examples

### PHP
```php
use Symfony\Component\Yaml\Yaml;

$rules = Yaml::parseFile('rules/claude-code.yml');

foreach ($rules['explicit_markers']['commit_footers'] as $marker) {
    if (preg_match('/' . $marker['pattern'] . '/i', $prDescription)) {
        return [
            'detected_tool' => $rules['tool']['name'],
            'confidence' => $marker['confidence'],
        ];
    }
}
```

### Python
```python
import yaml
import re

with open('rules/claude-code.yml') as f:
    rules = yaml.safe_load(f)

for marker in rules['explicit_markers']['commit_footers']:
    if re.search(marker['pattern'], pr_description, re.I):
        return {
            'detected_tool': rules['tool']['name'],
            'confidence': marker['confidence']
        }
```

### Node.js
```javascript
const yaml = require('js-yaml');
const fs = require('fs');

const rules = yaml.load(fs.readFileSync('rules/claude-code.yml'));

for (const marker of rules.explicit_markers.commit_footers) {
    if (new RegExp(marker.pattern, 'i').test(prDescription)) {
        return {
            detectedTool: rules.tool.name,
            confidence: marker.confidence
        };
    }
}
```

## Rule Categories

- **commit_footers** - Patterns in PR descriptions
- **co_author_attributions** - Co-author tags in commits
- **bot_authors** - Bot usernames and emails
- **html_comments** - Special HTML comments
- **labels** - PR labels
- **branch_patterns** - Branch naming conventions

## Available Rules

| Tool | File | Accuracy |
|------|------|----------|
| Claude Code | `claude-code.yml` | 100% |
| GitHub Copilot | `github-copilot.yml` | 100% |
| Cursor | `cursor.yml` | 96% |
| OpenAI Codex | `openai-codex.yml` | 100% |
| Devin | `devin.yml` | N/A |

## Contributing

1. Create `rules/tool-name.yml`
2. Follow existing format
3. Test against real PRs
4. Submit PR with accuracy metrics

## License

MIT License

## Credits

Created by [CoderBuds](https://coderbuds.com)
