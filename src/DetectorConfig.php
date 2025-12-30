<?php

namespace CoderBuds\AiDetector;

use CoderBuds\AiDetector\Enums\ScoringStrategy;

class DetectorConfig
{
    public function __construct(
        private array $detectors = [],
        private array $detectorConfig = [],
        private array $scoringConfig = [],
        private int $aiThreshold = 50,
    ) {
        // Set defaults if not provided
        if (empty($this->detectors)) {
            $this->detectors = $this->defaultDetectors();
        }

        if (empty($this->scoringConfig)) {
            $this->scoringConfig = $this->defaultScoringConfig();
        }
    }

    /**
     * Create default configuration
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create from array
     */
    public static function fromArray(array $config): self
    {
        return new self(
            detectors: $config['detectors'] ?? [],
            detectorConfig: $config['detector_config'] ?? [],
            scoringConfig: $config['scoring'] ?? [],
            aiThreshold: $config['ai_threshold'] ?? 50,
        );
    }

    /**
     * Default detector configuration
     */
    private function defaultDetectors(): array
    {
        return [
            'explicit_attribution' => true,
            'commit_pattern' => true,
            'tool_fingerprint' => false, // Requires file content
            'code_structure' => false,   // Requires file content
            'linguistic' => false,       // Optional, lower value
            'ai_model' => false,         // Expensive, opt-in
        ];
    }

    /**
     * Default scoring configuration
     */
    private function defaultScoringConfig(): array
    {
        return [
            'strategy' => ScoringStrategy::WEIGHTED_AVERAGE->value,
            'early_exit_on_definitive' => true,
            'consensus_boost' => true,
            'uncertainty_threshold' => 40,
        ];
    }

    /**
     * Check if detector is enabled
     */
    public function isEnabled(string $detector): bool
    {
        return $this->detectors[$detector] ?? false;
    }

    /**
     * Enable a detector
     */
    public function enable(string $detector): self
    {
        $this->detectors[$detector] = true;

        return $this;
    }

    /**
     * Disable a detector
     */
    public function disable(string $detector): self
    {
        $this->detectors[$detector] = false;

        return $this;
    }

    /**
     * Enable all detectors
     */
    public function enableAll(): self
    {
        foreach (array_keys($this->defaultDetectors()) as $detector) {
            $this->detectors[$detector] = true;
        }

        return $this;
    }

    /**
     * Get detector-specific config
     */
    public function getDetectorConfig(string $detector, ?string $key = null): mixed
    {
        if ($key === null) {
            return $this->detectorConfig[$detector] ?? [];
        }

        return $this->detectorConfig[$detector][$key] ?? null;
    }

    /**
     * Set detector-specific config
     */
    public function setDetectorConfig(string $detector, string $key, mixed $value): self
    {
        if (! isset($this->detectorConfig[$detector])) {
            $this->detectorConfig[$detector] = [];
        }

        $this->detectorConfig[$detector][$key] = $value;

        return $this;
    }

    /**
     * Get scoring strategy
     */
    public function getScoringStrategy(): ScoringStrategy
    {
        $strategy = $this->scoringConfig['strategy'] ?? 'weighted_average';

        return ScoringStrategy::from($strategy);
    }

    /**
     * Set scoring strategy
     */
    public function setScoringStrategy(ScoringStrategy $strategy): self
    {
        $this->scoringConfig['strategy'] = $strategy->value;

        return $this;
    }

    /**
     * Get scoring config value
     */
    public function getScoringConfig(string $key): mixed
    {
        return $this->scoringConfig[$key] ?? null;
    }

    /**
     * Should exit early on definitive result
     */
    public function shouldEarlyExit(): bool
    {
        return $this->scoringConfig['early_exit_on_definitive'] ?? true;
    }

    /**
     * Should apply consensus boosting
     */
    public function shouldApplyConsensusBoost(): bool
    {
        return $this->scoringConfig['consensus_boost'] ?? true;
    }

    /**
     * Get uncertainty threshold
     */
    public function getUncertaintyThreshold(): int
    {
        return $this->scoringConfig['uncertainty_threshold'] ?? 40;
    }

    /**
     * Get AI detection threshold
     */
    public function getAIThreshold(): int
    {
        return $this->aiThreshold;
    }

    /**
     * Set AI detection threshold
     */
    public function setAIThreshold(int $threshold): self
    {
        $this->aiThreshold = max(0, min(100, $threshold));

        return $this;
    }

    /**
     * Get all enabled detectors
     */
    public function getEnabledDetectors(): array
    {
        return array_keys(array_filter($this->detectors));
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'detectors' => $this->detectors,
            'detector_config' => $this->detectorConfig,
            'scoring' => $this->scoringConfig,
            'ai_threshold' => $this->aiThreshold,
        ];
    }
}
