<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Pages;

use App\Enums\SocialLoginProvider;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\SocialLoginSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
class SocialLoginSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected string $view = 'filament.clusters.settings.pages.social-login-settings-page';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Social Login';

    protected static ?string $navigationLabel = 'Social Login';

    public ?array $data = [];

    /**
     * @var array<int, array{slug: string, label: string, using_env: bool, enabled: bool}>
     */
    public array $providerStatuses = [];

    public function mount(): void
    {
        $socialLoginSettings = app(SocialLoginSettings::class);

        $this->providerStatuses = collect(SocialLoginProvider::cases())
            ->map(fn (SocialLoginProvider $socialLoginProvider): array => [
                'slug' => $socialLoginProvider->value,
                'label' => $socialLoginProvider->label(),
                'using_env' => $socialLoginSettings->isUsingEnv($socialLoginProvider),
                'enabled' => $socialLoginSettings->isProviderEnabled($socialLoginProvider),
            ])
            ->all();

        $this->form->fill([
            'github_client_id' => $socialLoginSettings->github_client_id,
            'github_client_secret' => $socialLoginSettings->github_client_secret,
            'github_redirect_uri' => $socialLoginSettings->github_redirect_uri,
            'github_enabled' => $socialLoginSettings->github_enabled,
            'google_client_id' => $socialLoginSettings->google_client_id,
            'google_client_secret' => $socialLoginSettings->google_client_secret,
            'google_redirect_uri' => $socialLoginSettings->google_redirect_uri,
            'google_enabled' => $socialLoginSettings->google_enabled,
            'facebook_client_id' => $socialLoginSettings->facebook_client_id,
            'facebook_client_secret' => $socialLoginSettings->facebook_client_secret,
            'facebook_redirect_uri' => $socialLoginSettings->facebook_redirect_uri,
            'facebook_enabled' => $socialLoginSettings->facebook_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                        ->schema([
                            $this->providerCard(SocialLoginProvider::Github),
                            $this->providerCard(SocialLoginProvider::Google),
                            $this->providerCard(SocialLoginProvider::Facebook),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
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

        $socialLoginSettings = app(SocialLoginSettings::class);

        $socialLoginSettings->github_client_id = $data['github_client_id'] ?? null ?: null;
        $socialLoginSettings->github_client_secret = $data['github_client_secret'] ?? null ?: null;
        $socialLoginSettings->github_redirect_uri = $data['github_redirect_uri'] ?? null ?: null;
        $socialLoginSettings->github_enabled = (bool) ($data['github_enabled'] ?? false);

        $socialLoginSettings->google_client_id = $data['google_client_id'] ?? null ?: null;
        $socialLoginSettings->google_client_secret = $data['google_client_secret'] ?? null ?: null;
        $socialLoginSettings->google_redirect_uri = $data['google_redirect_uri'] ?? null ?: null;
        $socialLoginSettings->google_enabled = (bool) ($data['google_enabled'] ?? false);

        $socialLoginSettings->facebook_client_id = $data['facebook_client_id'] ?? null ?: null;
        $socialLoginSettings->facebook_client_secret = $data['facebook_client_secret'] ?? null ?: null;
        $socialLoginSettings->facebook_redirect_uri = $data['facebook_redirect_uri'] ?? null ?: null;
        $socialLoginSettings->facebook_enabled = (bool) ($data['facebook_enabled'] ?? false);

        $socialLoginSettings->save();

        $this->refreshProviderStatuses();

        Notification::make()
            ->success()
            ->title('Social login settings saved successfully')
            ->send();
    }

    private function providerCard(SocialLoginProvider $socialLoginProvider): Section
    {
        $slug = $socialLoginProvider->value;
        $defaultRedirect = url("/auth/{$slug}/callback");
        $isUsingEnv = $this->isProviderUsingEnv($socialLoginProvider);

        return Section::make()
            ->heading($socialLoginProvider->label())
            ->description($socialLoginProvider->description())
            ->icon($socialLoginProvider->heroicon())
            ->compact()
            ->schema([
                Toggle::make("{$slug}_enabled")
                    ->label('Enabled')
                    ->helperText('When disabled, the social login button is hidden from the login and register pages.')
                    ->live()
                    ->onColor('success')
                    ->offColor('gray')
                    ->columnSpanFull(),

                TextInput::make("{$slug}_client_id")
                    ->label('Client ID')
                    ->placeholder('xxxxxxxxxxxxxxxxxxxx')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get("{$slug}_enabled"))
                    ->required(fn (Get $get): bool => (bool) $get("{$slug}_enabled") && ! $isUsingEnv)
                    ->columnSpanFull(),

                TextInput::make("{$slug}_client_secret")
                    ->label('Client Secret')
                    ->placeholder('xxxxxxxxxxxxxxxxxxxx')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get("{$slug}_enabled"))
                    ->required(fn (Get $get): bool => (bool) $get("{$slug}_enabled") && ! $isUsingEnv)
                    ->columnSpanFull(),

                TextInput::make("{$slug}_redirect_uri")
                    ->label('Redirect URI')
                    ->helperText("Leave blank to use the default: {$defaultRedirect}")
                    ->placeholder($defaultRedirect)
                    ->url()
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get("{$slug}_enabled"))
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    private function isProviderUsingEnv(SocialLoginProvider $socialLoginProvider): bool
    {
        foreach ($this->providerStatuses as $providerStatus) {
            if ($providerStatus['slug'] === $socialLoginProvider->value) {
                return (bool) $providerStatus['using_env'];
            }
        }

        return false;
    }

    private function refreshProviderStatuses(): void
    {
        $socialLoginSettings = app(SocialLoginSettings::class);

        $this->providerStatuses = collect(SocialLoginProvider::cases())
            ->map(fn (SocialLoginProvider $socialLoginProvider): array => [
                'slug' => $socialLoginProvider->value,
                'label' => $socialLoginProvider->label(),
                'using_env' => $socialLoginSettings->isUsingEnv($socialLoginProvider),
                'enabled' => $socialLoginSettings->isProviderEnabled($socialLoginProvider),
            ])
            ->all();
    }
}
