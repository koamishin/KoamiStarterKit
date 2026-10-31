<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\AiConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAskCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:ask {question : The question to ask the application assistant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask the configured AI assistant a question';

    public function handle(AiConfiguration $aiConfiguration): int
    {
        if (! $aiConfiguration->isEnabled()) {
            $this->error('AI is not enabled or is missing credentials. Configure it under Settings > AI & MCP.');

            return self::FAILURE;
        }

        try {
            $response = $aiConfiguration->prompt((string) $this->argument('question'));
        } catch (Throwable $exception) {
            Log::error('The AI provider request failed.', [
                'exception' => $exception->getMessage(),
            ]);

            $this->error("The AI provider request failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->line($response->text);

        return self::SUCCESS;
    }
}
