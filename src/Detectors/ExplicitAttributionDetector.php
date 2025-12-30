<?php

namespace CoderBuds\AiDetector\Detectors;

use CoderBuds\AiDetector\Contracts\DetectorInterface;
use CoderBuds\AiDetector\Data\DetectionIndicator;
use CoderBuds\AiDetector\Data\DetectionResult;
use CoderBuds\AiDetector\Data\PullRequestData;
use CoderBuds\AiDetector\Enums\ConfidenceLevel;
use CoderBuds\AiDetector\Enums\IndicatorType;
use CoderBuds\AiDetector\Enums\IndicatorWeight;

class ExplicitAttributionDetector implements DetectorInterface
{
    public function detect(PullRequestData $data): DetectionResult
    {
        $startTime = microtime(true);
        $detectedTool = null;
        $indicators = [];
        $score = 0;

        // 1. Check for AI bot authors in commits (most definitive)
        $botAuthorResult = $this->hasAIBotAuthor($data->commits);
        if ($botAuthorResult) {
            $detectedTool = $botAuthorResult['tool'];
            $score = 100;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::EXPLICIT_ATTRIBUTION,
                details: $botAuthorResult['details'],
                weight: IndicatorWeight::STRONG,
                score: 100,
            );
        }

        // 2. Claude Code attribution in PR description
        if ($this->hasClaudeCodeAttribution($data->description)) {
            $detectedTool = $detectedTool ?? 'Claude Code';
            $score = 100;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::EXPLICIT_ATTRIBUTION,
                details: 'PR description contains Claude Code footer',
                weight: IndicatorWeight::STRONG,
                score: 100,
            );
        }

        // 3. GitHub Copilot attribution
        if ($this->hasCopilotAttribution($data->description)) {
            $detectedTool = $detectedTool ?? 'GitHub Copilot';
            $score = 100;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::EXPLICIT_ATTRIBUTION,
                details: 'PR description contains GitHub Copilot attribution',
                weight: IndicatorWeight::STRONG,
                score: 100,
            );
        }

        // 4. Cursor attribution
        if ($this->hasCursorAttribution($data->description)) {
            $detectedTool = $detectedTool ?? 'Cursor';
            $score = 100;
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::EXPLICIT_ATTRIBUTION,
                details: 'PR description contains Cursor attribution',
                weight: IndicatorWeight::STRONG,
                score: 100,
            );
        }

        // 5. Codex/AI labels or branch markers
        if ($this->hasCodexMarkers($data)) {
            $detectedTool = $detectedTool ?? 'OpenAI Codex';
            $score = max($score, 100);
            $indicators[] = new DetectionIndicator(
                type: IndicatorType::EXPLICIT_ATTRIBUTION,
                details: 'Codex label or branch prefix detected',
                weight: IndicatorWeight::STRONG,
                score: 100,
            );
        }

        $processingTime = microtime(true) - $startTime;

        $confidence = $score === 100 ? ConfidenceLevel::DEFINITIVE : ConfidenceLevel::UNCERTAIN;

        $reasoning = $score === 100
            ? "Explicit AI tool attribution found: {$detectedTool}"
            : 'No explicit AI attribution markers found';

        return new DetectionResult(
            score: $score,
            confidence: $confidence,
            detectedTool: $detectedTool,
            reasoning: $reasoning,
            indicators: $indicators,
            detectorName: $this->getName(),
            processingTime: $processingTime,
        );
    }

    public function getName(): string
    {
        return 'explicit_attribution';
    }

    public function getConfidenceWeight(): float
    {
        return 1.0; // Highest confidence - explicit markers are definitive
    }

    public function requiresExternalAPI(): bool
    {
        return false;
    }

    /**
     * Check for Claude Code attribution in description
     */
    private function hasClaudeCodeAttribution(?string $description): bool
    {
        if ($description === null) {
            return false;
        }

        $patterns = [
            '/🤖\s*Generated with.*Claude Code/i',
            '/Co-Authored-By:\s*Claude.*noreply@anthropic\.com/i',
            '/\[Claude Code\]\(https:\/\/claude\.com\/claude-code\)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for GitHub Copilot attribution
     */
    private function hasCopilotAttribution(?string $description): bool
    {
        if ($description === null) {
            return false;
        }

        $patterns = [
            '/🤖\s*Generated with.*Copilot/i',
            '/Co-Authored-By:.*GitHub Copilot/i',
            '/\[GitHub Copilot\]/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for Cursor attribution
     */
    private function hasCursorAttribution(?string $description): bool
    {
        if ($description === null) {
            return false;
        }

        $patterns = [
            '/🤖\s*Generated with.*Cursor/i',
            '/Co-Authored-By:.*Cursor/i',
            '/\[Cursor\]/i',
            '/cursor\.so/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for Codex label or markers
     */
    private function hasCodexMarkers(PullRequestData $data): bool
    {
        // Check labels
        if ($data->hasLabel('codex') || $data->hasLabel('ai-generated') || $data->hasLabel('copilot')) {
            return true;
        }

        // Check branch naming patterns
        if ($data->branch && (
            str_contains(strtolower($data->branch), 'codex/') ||
            str_contains(strtolower($data->branch), 'codex-') ||
            str_contains(strtolower($data->branch), 'chatgpt-') ||
            str_contains(strtolower($data->branch), 'cursor-')
        )) {
            return true;
        }

        return false;
    }

    /**
     * Check commit authors for AI bot patterns
     */
    private function hasAIBotAuthor(array $commits): ?array
    {
        foreach ($commits as $commit) {
            $username = strtolower($commit->author->username ?? '');
            $email = strtolower($commit->author->email);
            $name = strtolower($commit->author->name);

            // GitHub Copilot
            if (str_contains($username, 'copilot[bot]') ||
                str_contains($username, 'github-copilot') ||
                str_contains($email, 'copilot@github.com')) {
                return [
                    'tool' => 'GitHub Copilot',
                    'details' => "Commit authored by GitHub Copilot bot ({$username})",
                ];
            }

            // Claude Code / Anthropic
            if (str_contains($email, 'noreply@anthropic.com') ||
                str_contains($email, 'claude@anthropic.com') ||
                str_contains($username, 'claude-bot') ||
                str_contains($name, 'claude sonnet')) {
                return [
                    'tool' => 'Claude Code',
                    'details' => "Commit authored by Claude bot ({$email})",
                ];
            }

            // Devin
            if (str_contains($username, 'devin-bot') ||
                str_contains($email, 'bot@devin.ai') ||
                str_contains($email, '@devin.ai')) {
                return [
                    'tool' => 'Devin',
                    'details' => "Commit authored by Devin bot ({$username})",
                ];
            }

            // Cursor
            if (str_contains($username, 'cursor-bot') ||
                str_contains($email, '@cursor.so') ||
                str_contains($email, 'cursor@')) {
                return [
                    'tool' => 'Cursor',
                    'details' => "Commit authored by Cursor bot ({$email})",
                ];
            }

            // WindSurf
            if (str_contains($username, 'windsurf') ||
                str_contains($email, '@windsurf.')) {
                return [
                    'tool' => 'WindSurf',
                    'details' => "Commit authored by WindSurf bot ({$email})",
                ];
            }
        }

        return null;
    }
}
