<?php

namespace CoderBuds\AiDetector\Contracts;

use CoderBuds\AiDetector\Data\AggregatedResult;

interface ScoringStrategyInterface
{
    /**
     * Aggregate multiple detection results into a single result
     *
     * @param array<int, \CoderBuds\AiDetector\Data\DetectionResult> $results
     */
    public function aggregate(array $results): AggregatedResult;
}
