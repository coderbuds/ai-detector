<?php

namespace CoderBuds\AiDetector\Data;

use DateTimeInterface;
use Spatie\LaravelData\Data;

class CommitData extends Data
{
    public function __construct(
        public string $sha,
        public string $message,
        public AuthorData $author,
        public DateTimeInterface $timestamp,
        public array $files = [],
    ) {}

    /**
     * Get commit message length
     */
    public function messageLength(): int
    {
        return strlen($this->message);
    }

    /**
     * Check if message contains specific pattern
     */
    public function messageContains(string $pattern): bool
    {
        return str_contains(strtolower($this->message), strtolower($pattern));
    }

    /**
     * Check if message matches regex pattern
     */
    public function messageMatches(string $pattern): bool
    {
        return preg_match($pattern, $this->message) === 1;
    }
}
