<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Pages;

use App\Ai\AiConfiguration;
use App\Enums\AiProvider;
use App\Enums\McpConnection;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\AiSettings;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property-read Schema $form
 */
class AiSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected string $view = 'filament.clusters.settings.pages.ai-settings-page';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'AI & MCP';

    protected static ?string $navigationLabel = 'AI & MCP';

    public ?array $data = [];

    public bool $usesEnvApiKey = false;

    public function mount(): void
    {
        $aiSettings = app(AiSettings::class);

        $this->usesEnvApiKey = $aiSettings->usesEnvApiKey();

        $this->form->fill([
            'enabled' => $aiSettings->enabled,
            'provider' => $aiSettings->provider,
            'api_key' => $aiSettings->api_key,
            'model' => $aiSettings->model,
            'base_url' => $aiSettings->base_url,
            'mcp_enabled' => $aiSettings->mcp_enabled,
            'mcp_servers' => $aiSettings->mcp_servers,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('AI Provider')
                        ->description('Configure the Laravel AI SDK provider used across the application.')
                        ->schema([
                            Toggle::make('enabled')
                                ->label('Enable AI')
                                ->helperText('When disabled, AI features such as ai:ask are turned off.')
                                ->live()
                                ->onColor('success')
                                ->offColor('gray')
                                ->columnSpanFull(),

                            SelectField::make('provider')
                                ->label('Provider')
                                ->options(AiProvider::options())
                                ->searchable()
                                ->live()
                                ->required()
                                ->helperText(fn (): ?string => $this->provider()?->description()),

                            FlexTextInput::make('model')
                                ->label('Model')
                                ->placeholder(fn (): string => $this->provider()?->defaultModel() ?? 'model-name')
                                ->maxLength(255)
                                ->required(fn (): bool => $this->provider()?->defaultModel() === null)
                                ->helperText('Leave blank to use the provider default model.'),

                            FlexTextInput::make('api_key')
                                ->label('API Key')
                                ->password()
                                ->maxLength(255)
                                ->visible(fn (): bool => $this->provider()?->requiresApiKey() ?? true)
                                ->required(fn (): bool => $this->getEnabled()
                                    && ($this->provider()?->requiresApiKey() ?? true)
                                    && ! $this->usesEnvApiKey)
                                ->helperText(fn (): string => $this->usesEnvApiKey
                                    ? 'Provided by the environment; the saved value is ignored.'
                                    : 'Stored in the database and used when no environment key is set.'),

                            FlexTextInput::make('base_url')
                                ->label('Base URL')
                                ->placeholder('https://api.openai.com/v1')
                                ->url()
                                ->maxLength(255)
                                ->visible(fn (): bool => $this->provider()?->supportsCustomBaseUrl() ?? false)
                                ->helperText('Override the provider endpoint, for example for a proxy or a local Ollama server.'),
                        ])
                        ->columns(2),

                    Section::make('MCP Servers')
                        ->description('Connect Model Context Protocol servers so the AI agent can use their tools.')
                        ->schema([
                            Toggle::make('mcp_enabled')
                                ->label('Enable MCP Tools')
                                ->helperText('When enabled, tools from every valid server below are exposed to AI agents.')
                                ->live()
                                ->onColor('success')
                                ->offColor('gray')
                                ->columnSpanFull(),

                            Repeater::make('mcp_servers')
                                ->label('Servers')
                                ->schema([
                                    FlexTextInput::make('name')
                                        ->label('Name')
                                        ->placeholder('github')
                                        ->maxLength(100)
                                        ->required()
                                        ->columnSpanFull(),

                                    SelectField::make('connection')
                                        ->label('Connection')
                                        ->options(McpConnection::options())
                                        ->default(McpConnection::Web->value)
                                        ->live()
                                        ->required(),

                                    FlexTextInput::make('url')
                                        ->label('URL')
                                        ->placeholder('https://mcp.example.com')
                                        ->url()
                                        ->maxLength(255)
                                        ->required(fn (Get $get): bool => $get('connection') === McpConnection::Web->value)
                                        ->visible(fn (Get $get): bool => $get('connection') === McpConnection::Web->value),

                                    FlexTextInput::make('token')
                                        ->label('Bearer Token')
                                        ->password()
                                        ->maxLength(255)
                                        ->visible(fn (Get $get): bool => $get('connection') === McpConnection::Web->value),

                                    NumberStepper::make('timeout')
                                        ->label('Timeout (seconds)')
                                        ->default(30)
                                        ->minValue(1)
                                        ->maxValue(300)
                                        ->step(5)
                                        ->required(),

                                    FlexTextInput::make('command')
                                        ->label('Command')
                                        ->placeholder('npx')
                                        ->maxLength(255)
                                        ->required(fn (Get $get): bool => $get('connection') === McpConnection::Local->value)
                                        ->visible(fn (Get $get): bool => $get('connection') === McpConnection::Local->value),

                                    TagsInput::make('arguments')
                                        ->label('Arguments')
                                        ->placeholder('-y @modelcontextprotocol/server-everything')
                                        ->visible(fn (Get $get): bool => $get('connection') === McpConnection::Local->value),
                                ])
                                ->visible(fn (Get $get): bool => (bool) $get('mcp_enabled'))
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->reorderable()
                                ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? (string) $state['name'] : null),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('test_connection')
                                ->label('Test Connection')
                                ->icon('heroicon-o-signal')
                                ->visible(fn (): bool => $this->getEnabled())
                                ->action(function (): void {
                                    $this->testConnection();
                                }),

                            Action::make('save')
                                ->label('Save Settings')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $aiSettings = app(AiSettings::class);

        $aiSettings->enabled = (bool) ($data['enabled'] ?? false);
        $aiSettings->provider = (string) ($data['provider'] ?? AiProvider::OpenAI->value);
        $aiSettings->api_key = filled($data['api_key'] ?? null) ? (string) $data['api_key'] : null;
        $aiSettings->model = filled($data['model'] ?? null) ? (string) $data['model'] : null;
        $aiSettings->base_url = filled($data['base_url'] ?? null) ? (string) $data['base_url'] : null;
        $aiSettings->mcp_enabled = (bool) ($data['mcp_enabled'] ?? false);
        $aiSettings->mcp_servers = array_values((array) ($data['mcp_servers'] ?? []));

        $aiSettings->save();

        $this->usesEnvApiKey = $aiSettings->usesEnvApiKey();

        Notification::make()
            ->success()
            ->title('AI settings saved successfully')
            ->send();
    }

    protected function testConnection(): void
    {
        $aiConfiguration = app(AiConfiguration::class);

        if (! $aiConfiguration->isEnabled()) {
            Notification::make()
                ->warning()
                ->title('AI is not configured yet')
                ->body('Save your provider settings before testing the connection.')
                ->send();

            return;
        }

        try {
            $response = $aiConfiguration->prompt('Reply with the single word: ok');
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Connection test failed')
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Connection successful')
            ->body(Str::limit($response->text, 120))
            ->send();
    }

    protected function provider(): ?AiProvider
    {
        $provider = $this->data['provider'] ?? null;

        return is_string($provider) ? AiProvider::tryFrom($provider) : null;
    }

    protected function getEnabled(): bool
    {
        return (bool) ($this->data['enabled'] ?? false);
    }
}
