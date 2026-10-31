<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Settings\SystemSettings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SiteInformationTool implements Tool
{
    public function __construct(protected SystemSettings $systemSettings) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get the name, description, and formatting details of this application.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'site_name' => $this->systemSettings->site_name,
            'site_description' => $this->systemSettings->site_description,
            'timezone' => $this->systemSettings->timezone,
            'date_format' => $this->systemSettings->date_format,
            'time_format' => $this->systemSettings->time_format,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
