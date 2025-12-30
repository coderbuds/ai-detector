<?php

namespace CoderBuds\AiDetector\Data;

use CoderBuds\AiDetector\Enums\ConfidenceLevel;
use Spatie\LaravelData\Data;

class AggregatedResult extends Data
{
    public function __construct(
        public int $finalScore,
        public ConfidenceLevel $finalConfidence,
        public ?string $detectedTool,
        public string $reasoning,
        /** @var array<int, DetectionResult> */
        public array $detectorResults,
        /** @var array<int, DetectionIndicator> */
        public array $aggregatedIndicators,
        public ScoringMetadata $metadata,
    ) {}

    /**
     * Check if AI generated based on threshold
     */
    public function isAIGenerated(int $threshold = 50): bool
    {
        return $this->finalScore >= $threshold;
    }

    /**
     * Get breakdown of detector scores for debugging
     */
    public function getDetectorBreakdown(): array
    {
        $breakdown = [];

        foreach ($this->detectorResults as $result) {
            $breakdown[$result->detectorName] = [
                'score' => $result->score,
                'confidence' => $result->confidence->value,
                'tool' => $result->detectedTool,
                'processing_time' => $result->processingTime,
            ];
        }

        return $breakdown;
    }

    /**
     * Get all indicators grouped by type
     */
    public function getIndicatorsByType(): array
    {
        $grouped = [];

        foreach ($this->aggregatedIndicators as $indicator) {
            $type = $indicator->type->value;

            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }

            $grouped[$type][] = $indicator;
        }

        return $grouped;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'final_score' => $this->finalScore,
            'final_confidence' => $this->finalConfidence->value,
            'confidence_description' => $this->finalConfidence->description(),
            'detected_tool' => $this->detectedTool,
            'reasoning' => $this->reasoning,
            'detector_results' => array_map(fn($r) => $r->toArray(), $this->detectorResults),
            'aggregated_indicators' => array_map(fn($i) => $i->toArray(), $this->aggregatedIndicators),
            'metadata' => $this->metadata->toArray(),
            'detector_breakdown' => $this->getDetectorBreakdown(),
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson($options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $options);
    }
}
