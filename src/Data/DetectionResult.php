<?php

namespace CoderBuds\AiDetector\Data;

use CoderBuds\AiDetector\Enums\ConfidenceLevel;
use Spatie\LaravelData\Data;

class DetectionResult extends Data
{
    public function __construct(
        public int $score,
        public ConfidenceLevel $confidence,
        public ?string $detectedTool,
        public string $reasoning,
        /** @var array<int, DetectionIndicator> */
        public array $indicators,
        public string $detectorName,
        public float $processingTime,
    ) {}

    /**
     * Check if this is a definitive result
     */
    public function isDefinitive(): bool
    {
        return $this->confidence === ConfidenceLevel::DEFINITIVE;
    }

    /**
     * Check if AI generated based on threshold
     */
    public function isAIGenerated(int $threshold = 50): bool
    {
        return $this->score >= $threshold;
    }

    /**
     * Get confidence as percentage
     */
    public function confidencePercentage(): int
    {
        return $this->confidence->numericValue();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'confidence' => $this->confidence->value,
            'confidence_description' => $this->confidence->description(),
            'detected_tool' => $this->detectedTool,
            'reasoning' => $this->reasoning,
            'indicators' => array_map(fn($i) => $i->toArray(), $this->indicators),
            'detector_name' => $this->detectorName,
            'processing_time' => $this->processingTime,
        ];
    }
}
