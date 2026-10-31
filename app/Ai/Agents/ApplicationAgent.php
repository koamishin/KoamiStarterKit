<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\AiConfiguration;
use App\Ai\Tools\SiteInformationTool;
use App\Settings\SystemSettings;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class ApplicationAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(
        protected AiConfiguration $aiConfiguration,
        protected SystemSettings $systemSettings,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return implode(PHP_EOL, [
            "You are the built-in assistant for {$this->systemSettings->site_name}.",
            $this->systemSettings->site_description,
            'Answer concisely and accurately, and use the tools available to you when they help answer the question.',
        ]);
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return array<int, Tool|object>
     */
    public function tools(): iterable
    {
        return [
            app(SiteInformationTool::class),
            ...$this->aiConfiguration->mcpTools(),
        ];
    }
}
