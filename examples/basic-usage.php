<?php

require_once __DIR__.'/../vendor/autoload.php';

use CoderBuds\AiDetector\Data\AuthorData;
use CoderBuds\AiDetector\Data\CommitData;
use CoderBuds\AiDetector\Data\PullRequestData;
use CoderBuds\AiDetector\Data\PullRequestMetadata;
use CoderBuds\AiDetector\DetectorConfig;
use CoderBuds\AiDetector\DetectorManager;
use CoderBuds\AiDetector\Detectors\CommitPatternDetector;
use CoderBuds\AiDetector\Detectors\ExplicitAttributionDetector;

echo "=== AI Detector Example ===\n\n";

// Create configuration
$config = DetectorConfig::default()
    ->enable('explicit_attribution')
    ->enable('commit_pattern');

// Create detector manager
$manager = DetectorManager::create($config);

// Register detectors
$manager->register(new ExplicitAttributionDetector())
        ->register(new CommitPatternDetector($config));

// Example 1: PR with Claude Code attribution (should be detected as definitive)
echo "Example 1: Claude Code PR\n";
echo str_repeat('-', 50)."\n";

$claudePR = new PullRequestData(
    title: 'Add user authentication feature',
    description: "This PR adds comprehensive JWT authentication.\n\n🤖 Generated with [Claude Code](https://claude.com/claude-code)\n\nCo-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>",
    commits: [
        new CommitData(
            sha: 'abc123',
            message: 'feat: Implement JWT authentication with secure token generation and validation',
            author: new AuthorData('John Doe', 'john@example.com'),
            timestamp: new DateTime('2024-01-15 10:30:00'),
        ),
    ],
    branch: 'feature/auth',
    labels: [],
    metadata: new PullRequestMetadata(
        additions: 150,
        deletions: 20,
        changedFiles: 5,
        openedAt: new DateTime('2024-01-15 10:30:00'),
    ),
);

$result = $manager->detect($claudePR);

echo "Score: {$result->finalScore}%\n";
echo "Confidence: {$result->finalConfidence->value}\n";
echo "Detected Tool: ".($result->detectedTool ?? 'None')."\n";
echo "Reasoning: {$result->reasoning}\n";
echo "Detectors Run: {$result->metadata->detectorsRun}\n";
echo "Processing Time: ".round($result->metadata->totalProcessingTime * 1000, 2)."ms\n\n";

// Example 2: Human PR with typo corrections (should be detected as human)
echo "Example 2: Human PR with corrections\n";
echo str_repeat('-', 50)."\n";

$humanPR = new PullRequestData(
    title: 'fix login bug',
    description: 'Fixed the bug where users couldnt login',
    commits: [
        new CommitData(
            sha: 'def456',
            message: 'fix login form',
            author: new AuthorData('Jane Smith', 'jane@example.com'),
            timestamp: new DateTime('2024-01-15 09:00:00'),
        ),
        new CommitData(
            sha: 'ghi789',
            message: 'oops forgot to update validation',
            author: new AuthorData('Jane Smith', 'jane@example.com'),
            timestamp: new DateTime('2024-01-15 09:45:00'),
        ),
        new CommitData(
            sha: 'jkl012',
            message: 'fix typo in error message',
            author: new AuthorData('Jane Smith', 'jane@example.com'),
            timestamp: new DateTime('2024-01-15 10:15:00'),
        ),
    ],
    branch: 'bugfix/login',
    labels: [],
    metadata: new PullRequestMetadata(
        additions: 30,
        deletions: 15,
        changedFiles: 2,
        openedAt: new DateTime('2024-01-15 09:00:00'),
    ),
);

$result2 = $manager->detect($humanPR);

echo "Score: {$result2->finalScore}%\n";
echo "Confidence: {$result2->finalConfidence->value}\n";
echo "Detected Tool: ".($result2->detectedTool ?? 'None')."\n";
echo "Reasoning: {$result2->reasoning}\n";
echo "Detectors Run: {$result2->metadata->detectorsRun}\n";
echo "Processing Time: ".round($result2->metadata->totalProcessingTime * 1000, 2)."ms\n\n";

// Example 3: PR with bot author (GitHub Copilot)
echo "Example 3: GitHub Copilot PR (bot author)\n";
echo str_repeat('-', 50)."\n";

$copilotPR = new PullRequestData(
    title: 'Implement password hashing',
    description: 'Added bcrypt password hashing functionality',
    commits: [
        new CommitData(
            sha: 'mno345',
            message: 'Add password hashing with bcrypt',
            author: new AuthorData('GitHub Copilot', 'copilot@github.com', 'github-copilot[bot]'),
            timestamp: new DateTime('2024-01-15 11:00:00'),
        ),
    ],
    branch: 'feature/password-hash',
    labels: [],
    metadata: new PullRequestMetadata(
        additions: 80,
        deletions: 5,
        changedFiles: 3,
        openedAt: new DateTime('2024-01-15 11:00:00'),
    ),
);

$result3 = $manager->detect($copilotPR);

echo "Score: {$result3->finalScore}%\n";
echo "Confidence: {$result3->finalConfidence->value}\n";
echo "Detected Tool: ".($result3->detectedTool ?? 'None')."\n";
echo "Reasoning: {$result3->reasoning}\n";
echo "Detectors Run: {$result3->metadata->detectorsRun}\n";
echo "Processing Time: ".round($result3->metadata->totalProcessingTime * 1000, 2)."ms\n\n";

// Show detailed breakdown for Example 1
echo "Detailed Breakdown (Example 1):\n";
echo str_repeat('-', 50)."\n";
foreach ($result->getDetectorBreakdown() as $detectorName => $breakdown) {
    echo "{$detectorName}:\n";
    echo "  Score: {$breakdown['score']}%\n";
    echo "  Confidence: {$breakdown['confidence']}\n";
    echo "  Tool: ".($breakdown['tool'] ?? 'None')."\n";
    echo "  Time: ".round($breakdown['processing_time'] * 1000, 2)."ms\n\n";
}

echo "✅ Examples completed successfully!\n";
