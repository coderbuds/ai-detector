<?php

namespace CoderBuds\AiDetector\Data;

use Spatie\LaravelData\Data;

class ScoringMetadata extends Data
{
    public function __construct(
        public int $detectorsRun,
        public float $totalProcessingTime,
        public string $scoringStrategy,
        public bool $earlyExit,
        public int $apiCallsMade,
    ) {}

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'detectors_run' => $this->detectorsRun,
            'total_processing_time' => round($this->totalProcessingTime, 3),
            'scoring_strategy' => $this->scoringStrategy,
            'early_exit' => $this->earlyExit,
            'api_calls_made' => $this->apiCallsMade,
        ];
    }
}
