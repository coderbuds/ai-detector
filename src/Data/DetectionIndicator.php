<?php

namespace CoderBuds\AiDetector\Data;

use CoderBuds\AiDetector\Enums\IndicatorType;
use CoderBuds\AiDetector\Enums\IndicatorWeight;
use Spatie\LaravelData\Data;

class DetectionIndicator extends Data
{
    public function __construct(
        public IndicatorType $type,
        public string $details,
        public IndicatorWeight $weight,
        public int $score,
    ) {}

    /**
     * Get confidence level from weight
     */
    public function confidence(): string
    {
        return $this->weight->toConfidence();
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'details' => $this->details,
            'weight' => $this->weight->value,
            'score' => $this->score,
            'confidence' => $this->confidence(),
        ];
    }
}
