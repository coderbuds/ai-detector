<?php

namespace CoderBuds\AiDetector\Detectors;

use CoderBuds\AiDetector\Contracts\DetectorInterface;
use CoderBuds\AiDetector\Data\DetectionIndicator;
use CoderBuds\AiDetector\Data\DetectionResult;
use CoderBuds\AiDetector\Data\PullRequestData;
use CoderBuds\AiDetector\DetectorConfig;
use CoderBuds\AiDetector\Enums\ConfidenceLevel;
use CoderBuds\AiDetector\Enums\IndicatorType;
use CoderBuds\AiDetector\Enums\IndicatorWeight;

class CommitPatternDetector implements DetectorInterface
{
    public function __construct(
        private DetectorConfig $config,
    ) {}

    public function detect(PullRequestData $data): DetectionResult
    {
        $startTime = microtime(true);
        $indicators = [];
        $score = 0;

        // Need at least 1 commit to analyze
        if ($data->commitCount() === 0) {
            return $this->createUncertainResult(microtime(true) - $startTime);
        }

        // 1. Burst Commit Detection
        $burstScore = $this->detectBurstCommits($data, $indicators);
        $score += $burstScore;

        // 2. Perfect First Attempt Detection
        $perfectFirstScore = $this->detectPerfectFirstAttempt($data, $indicators);
        $score += $perfectFirstScore;

        // 3. Typo/Correction Ratio
        $typoScore = $this->detectTypoRatio($data, $indicators);
        $score += $typoScore;

        // 4. Commit Message Consistency
        $consistencyScore = $this->detectMessageConsistency($data, $indicators);
        $score += $consistencyScore;

        // Normalize score (max 100)
        $score = min($score, 100);

        $processingTime = microtime(true) - $startTime;

        // Determine confidence
        $confidence = ConfidenceLevel::fromScore($score);

        // Determine detected tool (if score is high enough)
        $detectedTool = $score >= 70 ? 'Unknown AI Tool' : null;

        // Build reasoning
        $reasoning = $this->buildReasoning($score, count($indicators));

        return new DetectionResult(
            score: $score,
            confidence: $confidence,
            detectedTool: $detectedTool,
            reasoning: $reasoning,
            indicators: $indicators,
            detectorName: $this->getName(),
            processingTime: $processingTime,
        );
    }

    public function getName(): string
    {
        return 'commit_pattern';
    }

    public function getConfidenceWeight(): float
    {
        return 0.85; // High confidence for temporal patterns
    }

    public function requiresExternalAPI(): bool
    {
        return false;
    }

    /**
     * Detect burst commits (AI creates multiple commits rapidly)
     */
    private function detectBurstCommits(PullRequestData $data, array &$indicators): int
    {
        $commits = $data->commits;
        if (count($commits) < 2) {
            return 0;
        }

        $burstThreshold = $this->config->getDetectorConfig('commit_pattern', 'burst_threshold_seconds') ?? 120;
        $burstCommits = 0;
        $totalPairs = 0;

        for ($i = 1; $i < count($commits); $i++) {
            $timeDiff = $commits[$i]->timestamp->getTimestamp() - $commits[$i - 1]->timestamp->getTimestamp();

            // Count lines changed (if available in message or metadata)
            $linesChanged = $data->metadata->totalLinesChanged() / count($commits); // Rough estimate

            if ($timeDiff <= $burstThreshold && $linesChanged > 20) {
                $burstCommits++;
            }

            $totalPairs++;
        }

        if ($burstCommits === 0) {
            return 0;
        }

        $burstRatio = $burstCommits / $totalPairs;

        // AI: > 50% burst commits, Human: < 20%
        if ($burstRatio > 0.5) {
            $score = 40;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::TEMPORAL_PATTERN,
                details: sprintf(
                    'Found %d burst commits (%.0f%% of commits within %d seconds)',
                    $burstCommits,
                    $burstRatio * 100,
                    $burstThreshold
                ),
                weight: IndicatorWeight::STRONG,
                score: $score,
            );

            return $score;
        } elseif ($burstRatio > 0.3) {
            $score = 25;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::TEMPORAL_PATTERN,
                details: sprintf('Moderate burst commit pattern (%.0f%%)', $burstRatio * 100),
                weight: IndicatorWeight::MODERATE,
                score: $score,
            );

            return $score;
        }

        return 0;
    }

    /**
     * Detect perfect first attempt (single comprehensive commit)
     */
    private function detectPerfectFirstAttempt(PullRequestData $data, array &$indicators): int
    {
        $minLines = $this->config->getDetectorConfig('commit_pattern', 'perfect_first_attempt_min_lines') ?? 50;

        // Only applies to single-commit PRs
        if ($data->commitCount() !== 1) {
            return 0;
        }

        $totalLines = $data->metadata->totalLinesChanged();

        if ($totalLines >= $minLines) {
            $score = 30;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::COMMIT_PATTERN,
                details: sprintf(
                    'Single perfect commit with %d lines changed (no follow-up corrections)',
                    $totalLines
                ),
                weight: IndicatorWeight::MODERATE,
                score: $score,
            );

            return $score;
        }

        return 0;
    }

    /**
     * Detect typo/correction ratio
     */
    private function detectTypoRatio(PullRequestData $data, array &$indicators): int
    {
        $correctionPatterns = [
            '/\b(fix|fixed|oops|typo|missed|forgot|wrong)\b/i',
            '/\b(WIP|work in progress)\b/i',
            '/\b(tmp|temp|temporary)\b/i',
            '/\b(debug|debugging)\b/i',
        ];

        $correctionCount = 0;

        foreach ($data->commits as $commit) {
            foreach ($correctionPatterns as $pattern) {
                if ($commit->messageMatches($pattern)) {
                    $correctionCount++;
                    break; // Count each commit only once
                }
            }
        }

        $correctionRatio = $correctionCount / $data->commitCount();

        // Human: 15-20% corrections, AI: <5%
        if ($correctionRatio < 0.05 && $data->commitCount() >= 3) {
            $score = 35;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::COMMIT_PATTERN,
                details: sprintf(
                    'Very low correction ratio (%.0f%% - typical AI pattern)',
                    $correctionRatio * 100
                ),
                weight: IndicatorWeight::MODERATE,
                score: $score,
            );

            return $score;
        } elseif ($correctionRatio > 0.2) {
            // This is a negative indicator (suggests human)
            return -15; // Reduce overall score
        }

        return 0;
    }

    /**
     * Detect commit message consistency
     */
    private function detectMessageConsistency(PullRequestData $data, array &$indicators): int
    {
        if ($data->commitCount() < 3) {
            return 0;
        }

        $messageLengths = [];
        $perfectGrammarCount = 0;
        $conventionalCommitCount = 0;

        foreach ($data->commits as $commit) {
            $message = trim($commit->message);
            $messageLengths[] = strlen($message);

            // Check for perfect grammar indicators
            if ($this->hasPerfectGrammar($message)) {
                $perfectGrammarCount++;
            }

            // Check for conventional commits (e.g., "feat:", "fix:")
            if (preg_match('/^(feat|fix|docs|style|refactor|test|chore)(\(.+\))?:/i', $message)) {
                $conventionalCommitCount++;
            }
        }

        // Calculate variance in message lengths
        $variance = $this->calculateVariance($messageLengths);
        $mean = array_sum($messageLengths) / count($messageLengths);
        $coefficientOfVariation = $mean > 0 ? $variance / $mean : 0;

        // AI: very consistent (low variance), all perfect grammar
        // Human: high variance, mixed grammar
        $score = 0;

        if ($coefficientOfVariation < 0.3 && $perfectGrammarCount / $data->commitCount() > 0.8) {
            $score = 30;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::COMMIT_PATTERN,
                details: sprintf(
                    'Very consistent commit messages (%.0f%% perfect grammar, low length variance)',
                    ($perfectGrammarCount / $data->commitCount()) * 100
                ),
                weight: IndicatorWeight::MODERATE,
                score: $score,
            );
        }

        return $score;
    }

    /**
     * Check if message has perfect grammar indicators
     */
    private function hasPerfectGrammar(string $message): bool
    {
        // Check for multi-sentence structure with proper punctuation
        $sentences = preg_split('/[.!?]+/', $message);
        $sentences = array_filter($sentences, fn($s) => strlen(trim($s)) > 0);

        if (count($sentences) > 1) {
            // Multi-sentence messages are more likely AI
            return true;
        }

        // Check for verbose explanatory messages
        if (strlen($message) > 100 && str_contains($message, "\n")) {
            return true;
        }

        // Check for structured sections
        if (preg_match('/^.+:\s*\n[-*]/m', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(fn($v) => ($v - $mean) ** 2, $values);

        return sqrt(array_sum($squaredDifferences) / count($values));
    }

    /**
     * Build reasoning text
     */
    private function buildReasoning(int $score, int $indicatorCount): string
    {
        if ($score >= 70) {
            return "Strong temporal patterns detected ({$indicatorCount} indicators) suggesting AI generation";
        } elseif ($score >= 40) {
            return "Moderate temporal patterns detected ({$indicatorCount} indicators) that could suggest AI assistance";
        } else {
            return 'Commit patterns appear consistent with human development workflow';
        }
    }

    /**
     * Create uncertain result when not enough data
     */
    private function createUncertainResult(float $processingTime): DetectionResult
    {
        return new DetectionResult(
            score: 0,
            confidence: ConfidenceLevel::UNCERTAIN,
            detectedTool: null,
            reasoning: 'Not enough commit data to analyze patterns',
            indicators: [],
            detectorName: $this->getName(),
            processingTime: $processingTime,
        );
    }
}
