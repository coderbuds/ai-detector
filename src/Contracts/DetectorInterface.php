<?php

namespace CoderBuds\AiDetector\Contracts;

use CoderBuds\AiDetector\Data\DetectionResult;
use CoderBuds\AiDetector\Data\PullRequestData;

interface DetectorInterface
{
    /**
     * Detect AI usage in a pull request
     */
    public function detect(PullRequestData $data): DetectionResult;

    /**
     * Get the detector name/identifier
     */
    public function getName(): string;

    /**
     * Get the confidence weight for this detector (0.0 to 1.0)
     * Used by the scoring engine to weight results
     */
    public function getConfidenceWeight(): float;

    /**
     * Whether this detector requires external API calls
     * (helps with cost optimization and ordering)
     */
    public function requiresExternalAPI(): bool;
}
