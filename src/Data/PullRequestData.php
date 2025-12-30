<?php

namespace CoderBuds\AiDetector\Data;

use Spatie\LaravelData\Data;

class PullRequestData extends Data
{
    public function __construct(
        public string $title,
        public ?string $description,
        /** @var array<int, CommitData> */
        public array $commits,
        public ?string $branch,
        public array $labels,
        public PullRequestMetadata $metadata,
    ) {}

    /**
     * Get number of commits
     */
    public function commitCount(): int
    {
        return count($this->commits);
    }

    /**
     * Check if description contains specific pattern
     */
    public function descriptionContains(string $pattern): bool
    {
        if ($this->description === null) {
            return false;
        }

        return str_contains(strtolower($this->description), strtolower($pattern));
    }

    /**
     * Check if description matches regex pattern
     */
    public function descriptionMatches(string $pattern): bool
    {
        if ($this->description === null) {
            return false;
        }

        return preg_match($pattern, $this->description) === 1;
    }

    /**
     * Check if branch name matches pattern
     */
    public function branchMatches(string $pattern): bool
    {
        if ($this->branch === null) {
            return false;
        }

        return preg_match($pattern, $this->branch) === 1;
    }

    /**
     * Check if PR has specific label
     */
    public function hasLabel(string $label): bool
    {
        return in_array(strtolower($label), array_map('strtolower', $this->labels));
    }
}
