<?php

namespace CoderBuds\AiDetector\Scoring\Strategies;

use CoderBuds\AiDetector\Contracts\ScoringStrategyInterface;
use CoderBuds\AiDetector\Data\AggregatedResult;
use CoderBuds\AiDetector\Data\ScoringMetadata;
use CoderBuds\AiDetector\DetectorConfig;
use CoderBuds\AiDetector\Enums\ConfidenceLevel;

class WeightedAverageStrategy implements ScoringStrategyInterface
{
    public function __construct(
        private DetectorConfig $config,
    ) {}

    /**
     * Aggregate results using weighted average
     */
    public function aggregate(array $results): AggregatedResult
    {
        if (empty($results)) {
            return $this->createUncertainResult($results);
        }

        // Calculate weighted average score
        $totalWeight = 0;
        $weightedScore = 0;
        $totalProcessingTime = 0;
        $apiCallsMade = 0;

        foreach ($results as $result) {
            $weight = $result->confidence->numericValue() / 100;
            $weightedScore += $result->score * $weight;
            $totalWeight += $weight;
            $totalProcessingTime += $result->processingTime;

            // Count API calls (detectors that require external API)
            if (str_contains($result->detectorName, 'ai_model')) {
                $apiCallsMade++;
            }
        }

        $finalScore = $totalWeight > 0 ? (int) round($weightedScore / $totalWeight) : 0;

        // Determine detected tool by consensus
        $detectedTool = $this->determineToolByConsensus($results);

        // Apply consensus boosting if configured
        if ($this->config->shouldApplyConsensusBoost()) {
            $finalScore = $this->applyConsensusBoost($results, $finalScore, $detectedTool);
        }

        // Determine confidence level
        $finalConfidence = $this->determineConfidence($results, $finalScore);

        // Aggregate all indicators
        $aggregatedIndicators = [];
        foreach ($results as $result) {
            $aggregatedIndicators = array_merge($aggregatedIndicators, $result->indicators);
        }

        // Build reasoning
        $reasoning = $this->buildReasoning($results, $finalScore, $detectedTool);

        // Create metadata
        $metadata = new ScoringMetadata(
            detectorsRun: count($results),
            totalProcessingTime: $totalProcessingTime,
            scoringStrategy: 'weighted_average',
            earlyExit: count($results) < 3, // Heuristic: early exit if < 3 detectors ran
            apiCallsMade: $apiCallsMade,
        );

        return new AggregatedResult(
            finalScore: $finalScore,
            finalConfidence: $finalConfidence,
            detectedTool: $detectedTool,
            reasoning: $reasoning,
            detectorResults: $results,
            aggregatedIndicators: $aggregatedIndicators,
            metadata: $metadata,
        );
    }

    /**
     * Determine tool by consensus (most votes)
     */
    private function determineToolByConsensus(array $results): ?string
    {
        $toolVotes = [];

        foreach ($results as $result) {
            if ($result->detectedTool !== null) {
                $toolVotes[$result->detectedTool] = ($toolVotes[$result->detectedTool] ?? 0) + 1;
            }
        }

        if (empty($toolVotes)) {
            return null;
        }

        arsort($toolVotes);

        return array_key_first($toolVotes);
    }

    /**
     * Apply consensus boosting
     */
    private function applyConsensusBoost(array $results, int $baseScore, ?string $detectedTool): int
    {
        if ($detectedTool === null) {
            return $baseScore;
        }

        // Count how many detectors agreed on this tool
        $agreementCount = 0;
        foreach ($results as $result) {
            if ($result->detectedTool === $detectedTool) {
                $agreementCount++;
            }
        }

        // If 3+ detectors agree, boost confidence by 10%
        if ($agreementCount >= 3) {
            $baseScore = min($baseScore + 10, 100);
        }

        return $baseScore;
    }

    /**
     * Determine final confidence level
     */
    private function determineConfidence(array $results, int $finalScore): ConfidenceLevel
    {
        // Check for definitive results
        foreach ($results as $result) {
            if ($result->confidence === ConfidenceLevel::DEFINITIVE) {
                return ConfidenceLevel::DEFINITIVE;
            }
        }

        // Calculate variance in scores
        $scores = array_map(fn($r) => $r->score, $results);
        $variance = $this->calculateVariance($scores);

        // If high variance, reduce confidence
        if ($variance > $this->config->getUncertaintyThreshold()) {
            return ConfidenceLevel::UNCERTAIN;
        }

        // Otherwise determine by score
        return ConfidenceLevel::fromScore($finalScore);
    }

    /**
     * Calculate variance of scores
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
     * Build comprehensive reasoning
     */
    private function buildReasoning(array $results, int $finalScore, ?string $detectedTool): string
    {
        $parts = [];

        // Add score summary
        $parts[] = "Overall AI likelihood score: {$finalScore}%";

        // Add tool detection
        if ($detectedTool) {
            $parts[] = "Detected tool: {$detectedTool}";
        }

        // Add detector contributions
        $detectorSummaries = [];
        foreach ($results as $result) {
            $detectorSummaries[] = "{$result->detectorName}: {$result->score}% ({$result->confidence->value})";
        }

        $parts[] = 'Detector results: '.implode(', ', $detectorSummaries);

        return implode('. ', $parts).'.';
    }

    /**
     * Create uncertain result when no detectors ran
     */
    private function createUncertainResult(array $results): AggregatedResult
    {
        return new AggregatedResult(
            finalScore: 0,
            finalConfidence: ConfidenceLevel::UNCERTAIN,
            detectedTool: null,
            reasoning: 'No detectors were able to analyze this pull request.',
            detectorResults: $results,
            aggregatedIndicators: [],
            metadata: new ScoringMetadata(
                detectorsRun: 0,
                totalProcessingTime: 0,
                scoringStrategy: 'weighted_average',
                earlyExit: false,
                apiCallsMade: 0,
            ),
        );
    }
}
