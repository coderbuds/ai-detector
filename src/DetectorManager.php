<?php

namespace CoderBuds\AiDetector;

use CoderBuds\AiDetector\Contracts\DetectorInterface;
use CoderBuds\AiDetector\Data\AggregatedResult;
use CoderBuds\AiDetector\Data\PullRequestData;
use CoderBuds\AiDetector\Scoring\ConfidenceScoringEngine;

class DetectorManager
{
    /** @var array<string, DetectorInterface> */
    private array $detectors = [];

    public function __construct(
        private ConfidenceScoringEngine $scoringEngine,
        private DetectorConfig $config,
    ) {}

    /**
     * Create manager with default configuration
     */
    public static function create(?DetectorConfig $config = null): self
    {
        $config = $config ?? DetectorConfig::default();
        $scoringEngine = new ConfidenceScoringEngine($config);

        return new self($scoringEngine, $config);
    }

    /**
     * Register a detector
     */
    public function register(DetectorInterface $detector): self
    {
        $this->detectors[$detector->getName()] = $detector;

        return $this;
    }

    /**
     * Unregister a detector
     */
    public function unregister(string $detectorName): self
    {
        unset($this->detectors[$detectorName]);

        return $this;
    }

    /**
     * Check if detector is registered
     */
    public function hasDetector(string $detectorName): bool
    {
        return isset($this->detectors[$detectorName]);
    }

    /**
     * Get all registered detectors
     */
    public function getDetectors(): array
    {
        return $this->detectors;
    }

    /**
     * Run detection with all enabled detectors
     */
    public function detect(PullRequestData $data): AggregatedResult
    {
        $results = [];

        foreach ($this->detectors as $detector) {
            // Skip if detector is disabled in config
            if (! $this->config->isEnabled($detector->getName())) {
                continue;
            }

            try {
                $result = $detector->detect($data);
                $results[] = $result;

                // Early exit if definitive and configured
                if ($result->isDefinitive() && $this->config->shouldEarlyExit()) {
                    break;
                }
            } catch (\Exception $e) {
                // Log error but continue with other detectors
                // In production, you'd want proper logging here
                continue;
            }
        }

        return $this->scoringEngine->aggregate($results);
    }

    /**
     * Run detection with specific detectors only
     */
    public function detectWith(array $detectorNames, PullRequestData $data): AggregatedResult
    {
        $results = [];

        foreach ($detectorNames as $name) {
            if (! isset($this->detectors[$name])) {
                continue;
            }

            try {
                $results[] = $this->detectors[$name]->detect($data);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->scoringEngine->aggregate($results);
    }

    /**
     * Run detectors in optimized order (fast/free first, expensive last)
     */
    public function detectOptimized(PullRequestData $data): AggregatedResult
    {
        // Separate detectors by API requirement
        $freeDetectors = [];
        $apiDetectors = [];

        foreach ($this->detectors as $detector) {
            if (! $this->config->isEnabled($detector->getName())) {
                continue;
            }

            if ($detector->requiresExternalAPI()) {
                $apiDetectors[] = $detector;
            } else {
                $freeDetectors[] = $detector;
            }
        }

        // Run free detectors first
        $results = [];

        foreach ($freeDetectors as $detector) {
            try {
                $result = $detector->detect($data);
                $results[] = $result;

                // Early exit on definitive result
                if ($result->isDefinitive() && $this->config->shouldEarlyExit()) {
                    return $this->scoringEngine->aggregate($results);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Check if we have high confidence from free detectors
        $intermediateResult = $this->scoringEngine->aggregate($results);

        // If we have high confidence, maybe skip expensive detectors
        if ($intermediateResult->finalConfidence->numericValue() >= 80) {
            return $intermediateResult;
        }

        // Run API-based detectors if needed
        foreach ($apiDetectors as $detector) {
            try {
                $results[] = $detector->detect($data);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->scoringEngine->aggregate($results);
    }

    /**
     * Get configuration
     */
    public function getConfig(): DetectorConfig
    {
        return $this->config;
    }

    /**
     * Get scoring engine
     */
    public function getScoringEngine(): ConfidenceScoringEngine
    {
        return $this->scoringEngine;
    }
}
