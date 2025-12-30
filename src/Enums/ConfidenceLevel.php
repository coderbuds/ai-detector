<?php

namespace CoderBuds\AiDetector\Enums;

enum ConfidenceLevel: string
{
    case DEFINITIVE = 'Definitive';
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';
    case UNCERTAIN = 'Uncertain';

    /**
     * Get a human-readable description of the confidence level
     */
    public function description(): string
    {
        return match ($this) {
            self::DEFINITIVE => 'Definitive - Explicit AI tool attribution',
            self::HIGH => 'High - Very likely AI-assisted',
            self::MEDIUM => 'Medium - Probably AI-assisted',
            self::LOW => 'Low - Possibly AI-assisted',
            self::UNCERTAIN => 'Uncertain - Unable to determine',
        };
    }

    /**
     * Get numeric score for this confidence level (0-100)
     */
    public function numericValue(): int
    {
        return match ($this) {
            self::DEFINITIVE => 100,
            self::HIGH => 80,
            self::MEDIUM => 60,
            self::LOW => 40,
            self::UNCERTAIN => 0,
        };
    }

    /**
     * Create from score value
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 95 => self::DEFINITIVE,
            $score >= 75 => self::HIGH,
            $score >= 50 => self::MEDIUM,
            $score >= 25 => self::LOW,
            default => self::UNCERTAIN,
        };
    }
}
