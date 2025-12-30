# AI Detector

Multi-strategy AI code detection for pull requests.

## Overview

AI Detector is an open-source PHP package that detects AI-generated code in pull requests using multiple detection strategies:

- **Explicit Attribution Detection** - Bot authors, footers, labels (100% accuracy)
- **Commit Pattern Detection** - Temporal analysis, burst commits, typo ratios (85%+ accuracy)
- **Tool Fingerprint Detection** - Claude/Copilot/ChatGPT-specific patterns (75%+ accuracy)
- **AI Model Detection** - OpenAI/Claude powered analysis (configurable)

## Features

- 🎯 **Multi-strategy detection** - Combine multiple signals for higher accuracy
- 🔧 **Highly configurable** - Enable/disable detectors, customize thresholds
- 💰 **Cost-optimized** - Early exit when definitive results found
- 🔌 **Framework-agnostic** - Works standalone or with Laravel
- 📊 **Transparent** - See exactly which detectors triggered and why
- 🧩 **Extensible** - Easy to build custom detectors

## Installation

```bash
composer require coderbuds/ai-detector
```

## Quick Start

```php
use CoderBuds\AiDetector\DetectorManager;
use CoderBuds\AiDetector\DetectorConfig;
use CoderBuds\AiDetector\Data\PullRequestData;

// Initialize with default configuration
$manager = DetectorManager::create();

// Prepare PR data
$prData = new PullRequestData(/* ... */);

// Run detection
$result = $manager->detect($prData);

// Check if AI-generated
if ($result->isAIGenerated()) {
    echo "AI detected: {$result->detectedTool}\n";
    echo "Score: {$result->finalScore}%\n";
}
```

## Documentation

- [Installation & Configuration](docs/CONFIGURATION.md)
- [Available Detectors](docs/DETECTORS.md)
- [Laravel Integration](docs/LARAVEL_INTEGRATION.md)
- [Building Custom Detectors](docs/CUSTOM_DETECTORS.md)
- [Accuracy Metrics](docs/ACCURACY.md)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

Built by [CoderBuds](https://coderbuds.com) - AI-powered code review and team metrics.
