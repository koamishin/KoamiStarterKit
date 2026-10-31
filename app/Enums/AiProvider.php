<?php

declare(strict_types=1);

namespace App\Enums;

enum AiProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case Xai = 'xai';
    case DeepSeek = 'deepseek';
    case Mistral = 'mistral';
    case OpenRouter = 'openrouter';
    case Ollama = 'ollama';
    case OpenAiCompatible = 'openai-compatible';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::Anthropic => 'Anthropic',
            self::Gemini => 'Google Gemini',
            self::Groq => 'Groq',
            self::Xai => 'xAI',
            self::DeepSeek => 'DeepSeek',
            self::Mistral => 'Mistral',
            self::OpenRouter => 'OpenRouter',
            self::Ollama => 'Ollama',
            self::OpenAiCompatible => 'OpenAI Compatible',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OpenAI => 'GPT models via the OpenAI API',
            self::Anthropic => 'Claude models via the Anthropic API',
            self::Gemini => 'Gemini models via the Google AI API',
            self::Groq => 'Open models hosted on Groq',
            self::Xai => 'Grok models via the xAI API',
            self::DeepSeek => 'DeepSeek chat models',
            self::Mistral => 'Mistral models via the Mistral API',
            self::OpenRouter => 'Many models behind a single OpenAI-style API',
            self::Ollama => 'Locally hosted models via Ollama',
            self::OpenAiCompatible => 'Any OpenAI-compatible API endpoint',
        };
    }

    /**
     * The model used when no model has been configured in the settings.
     */
    public function defaultModel(): ?string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4o',
            self::Anthropic => 'claude-sonnet-4-5',
            self::Gemini => 'gemini-2.5-flash',
            self::Groq => 'llama-3.3-70b-versatile',
            self::Xai => 'grok-4',
            self::DeepSeek => 'deepseek-chat',
            self::Mistral => 'mistral-large-latest',
            self::OpenRouter => 'openai/gpt-4o',
            self::Ollama => 'llama3.2',
            self::OpenAiCompatible => null,
        };
    }

    /**
     * The environment variable that provides the API key for this provider.
     */
    public function envKey(): string
    {
        return match ($this) {
            self::OpenAI => 'OPENAI_API_KEY',
            self::Anthropic => 'ANTHROPIC_API_KEY',
            self::Gemini => 'GEMINI_API_KEY',
            self::Groq => 'GROQ_API_KEY',
            self::Xai => 'XAI_API_KEY',
            self::DeepSeek => 'DEEPSEEK_API_KEY',
            self::Mistral => 'MISTRAL_API_KEY',
            self::OpenRouter => 'OPENROUTER_API_KEY',
            self::Ollama => 'OLLAMA_API_KEY',
            self::OpenAiCompatible => 'OPENAI_COMPATIBLE_API_KEY',
        };
    }

    /**
     * The environment variable that overrides the provider base URL, if any.
     */
    public function envUrlKey(): string
    {
        return match ($this) {
            self::OpenAI => 'OPENAI_URL',
            self::Anthropic => 'ANTHROPIC_URL',
            self::Gemini => 'GEMINI_URL',
            self::Ollama => 'OLLAMA_URL',
            self::OpenAiCompatible => 'OPENAI_COMPATIBLE_URL',
            default => strtoupper($this->value).'_URL',
        };
    }

    public function requiresApiKey(): bool
    {
        return $this !== self::Ollama;
    }

    public function supportsCustomBaseUrl(): bool
    {
        return match ($this) {
            self::OpenAI, self::Anthropic, self::Gemini, self::Ollama, self::OpenAiCompatible => true,
            default => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (AiProvider $aiProvider): array => [$aiProvider->value => $aiProvider->label()])
            ->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
