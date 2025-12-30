<?php

namespace CoderBuds\AiDetector\Scoring;

use CoderBuds\AiDetector\Contracts\ScoringStrategyInterface;
use CoderBuds\AiDetector\Data\AggregatedResult;
use CoderBuds\AiDetector\DetectorConfig;
use CoderBuds\AiDetector\Enums\ScoringStrategy;
use CoderBuds\AiDetector\Scoring\Strategies\WeightedAverageStrategy;

class ConfidenceScoringEngine
{
    private ScoringStrategyInterface $strategy;

    public function __construct(
        private DetectorConfig $config,
    ) {
        $this->setStrategyFromConfig();
    }

    /**
     * Aggregate multiple detection results
     *
     * @param  array<int, \CoderBuds\AiDetector\Data\DetectionResult>  $results
     */
    public function aggregate(array $results): AggregatedResult
    {
        return $this->strategy->aggregate($results);
    }

    /**
     * Set scoring strategy
     */
    public function setStrategy(ScoringStrategyInterface $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set strategy from config
     */
    private function setStrategyFromConfig(): void
    {
        $strategyEnum = $this->config->getScoringStrategy();

        $this->strategy = match ($strategyEnum) {
            ScoringStrategy::WEIGHTED_AVERAGE => new WeightedAverageStrategy($this->config),
            ScoringStrategy::MAXIMUM_SCORE => throw new \RuntimeException('Maximum score strategy not yet implemented'),
            ScoringStrategy::CONSENSUS => throw new \RuntimeException('Consensus strategy not yet implemented'),
            ScoringStrategy::CUSTOM => throw new \RuntimeException('Custom strategy must be set manually'),
        };
    }
}
