<?php

namespace CoderBuds\AiDetector\Data;

use DateTimeInterface;
use Spatie\LaravelData\Data;

class PullRequestMetadata extends Data
{
    public function __construct(
        public int $additions,
        public int $deletions,
        public int $changedFiles,
        public DateTimeInterface $openedAt,
        public ?DateTimeInterface $mergedAt = null,
    ) {}

    /**
     * Get total lines changed
     */
    public function totalLinesChanged(): int
    {
        return $this->additions + $this->deletions;
    }

    /**
     * Check if PR is merged
     */
    public function isMerged(): bool
    {
        return $this->mergedAt !== null;
    }
}
