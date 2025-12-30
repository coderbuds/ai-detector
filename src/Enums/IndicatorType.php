<?php

namespace CoderBuds\AiDetector\Enums;

enum IndicatorType: string
{
    case EXPLICIT_ATTRIBUTION = 'explicit_attribution';
    case COMMIT_PATTERN = 'commit_pattern';
    case DESCRIPTION_STRUCTURE = 'description_structure';
    case CODE_STYLE = 'code_style';
    case TEMPORAL_PATTERN = 'temporal_pattern';
    case AUTHOR_INFO = 'author_info';
    case TOOL_FINGERPRINT = 'tool_fingerprint';
    case LINGUISTIC_PATTERN = 'linguistic_pattern';
    case OTHER = 'other';

    /**
     * Get a human-readable label for this indicator type
     */
    public function label(): string
    {
        return match ($this) {
            self::EXPLICIT_ATTRIBUTION => 'Explicit Attribution',
            self::COMMIT_PATTERN => 'Commit Pattern',
            self::DESCRIPTION_STRUCTURE => 'Description Structure',
            self::CODE_STYLE => 'Code Style',
            self::TEMPORAL_PATTERN => 'Temporal Pattern',
            self::AUTHOR_INFO => 'Author Information',
            self::TOOL_FINGERPRINT => 'Tool Fingerprint',
            self::LINGUISTIC_PATTERN => 'Linguistic Pattern',
            self::OTHER => 'Other',
        };
    }
}
