<?php

namespace CoderBuds\AiDetector\Enums;

enum IndicatorWeight: string
{
    case STRONG = 'strong';
    case MODERATE = 'moderate';
    case WEAK = 'weak';

    /**
     * Convert weight to numeric score (0-100)
     */
    public function numericValue(): int
    {
        return match ($this) {
            self::STRONG => 100,
            self::MODERATE => 50,
            self::WEAK => 25,
        };
    }

    /**
     * Get confidence level from weight
     */
    public function toConfidence(): string
    {
        return match ($this) {
            self::STRONG => 'definitive',
            self::MODERATE => 'high',
            self::WEAK => 'medium',
        };
    }
}
