<?php

namespace CoderBuds\AiDetector\Enums;

enum ScoringStrategy: string
{
    case WEIGHTED_AVERAGE = 'weighted_average';
    case MAXIMUM_SCORE = 'maximum';
    case CONSENSUS = 'consensus';
    case CUSTOM = 'custom';

    /**
     * Get description of this strategy
     */
    public function description(): string
    {
        return match ($this) {
            self::WEIGHTED_AVERAGE => 'Weighted average of all detector scores',
            self::MAXIMUM_SCORE => 'Take the highest score from all detectors',
            self::CONSENSUS => 'Require consensus across multiple detectors',
            self::CUSTOM => 'Custom user-provided scoring function',
        };
    }
}
