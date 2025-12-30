<?php

namespace CoderBuds\AiDetector\Data;

use Spatie\LaravelData\Data;

class AuthorData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $username = null,
    ) {}
}
