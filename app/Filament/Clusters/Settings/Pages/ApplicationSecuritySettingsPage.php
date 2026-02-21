<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\ApplicationSecuritySettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
class ApplicationSecuritySettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.clusters.settings.pages.application-security-settings-page';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Application Security';

    protected static ?string $navigationLabel = 'Application Security';

    public ?array $data = [];

    public function mount(): void
    {
        $applicationSecuritySettings = app(ApplicationSecuritySettings::class);

        $this->form->fill([
            'password_min_length' => $applicationSecuritySettings->password_min_length,
            'password_requires_uppercase' => $applicationSecuritySettings->password_requires_uppercase,
            'password_requires_lowercase' => $applicationSecuritySettings->password_requires_lowercase,
            'password_requires_numbers' => $applicationSecuritySettings->password_requires_numbers,
            'password_requires_symbols' => $applicationSecuritySettings->password_requires_symbols,
            'session_lifetime' => $applicationSecuritySettings->session_lifetime,
            'single_session' => $applicationSecuritySettings->single_session,
            'login_rate_limit' => $applicationSecuritySettings->login_rate_limit,
            'login_rate_limit_decay' => $applicationSecuritySettings->login_rate_limit_decay,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Password Policy')
                        ->description('Configure password requirements for users.')
                        ->schema([
                            TextInput::make('password_min_length')
                                ->label('Minimum Password Length')
                                ->required()
                                ->numeric()
                                ->minValue(6)
                                ->maxValue(128),
                            Toggle::make('password_requires_uppercase')
                                ->label('Require Uppercase Letter')
                                ->default(true),
                            Toggle::make('password_requires_lowercase')
                                ->label('Require Lowercase Letter')
                                ->default(true),
                            Toggle::make('password_requires_numbers')
                                ->label('Require Numbers')
                                ->default(true),
                            Toggle::make('password_requires_symbols')
                                ->label('Require Symbols')
                                ->default(false),
                        ])
                        ->columns(2),

                    Section::make('Session Settings')
                        ->description('Configure user session behavior.')
                        ->schema([
                            TextInput::make('session_lifetime')
                                ->label('Session Lifetime (minutes)')
                                ->required()
                                ->numeric()
                                ->minValue(5)
                                ->maxValue(1440),
                            Toggle::make('single_session')
                                ->label('Single Session per User')
                                ->default(false),
                        ])
                        ->columns(2),

                    Section::make('Login Protection')
                        ->description('Configure login rate limiting.')
                        ->schema([
                            TextInput::make('login_rate_limit')
                                ->label('Max Login Attempts')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100),
                            TextInput::make('login_rate_limit_decay')
                                ->label('Lockout Duration (seconds)')
                                ->required()
                                ->numeric()
                                ->minValue(30)
                                ->maxValue(3600),
                        ])
                        ->columns(2),
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

        $applicationSecuritySettings = app(ApplicationSecuritySettings::class);

        $applicationSecuritySettings->password_min_length = (int) $data['password_min_length'];
        $applicationSecuritySettings->password_requires_uppercase = $data['password_requires_uppercase'];
        $applicationSecuritySettings->password_requires_lowercase = $data['password_requires_lowercase'];
        $applicationSecuritySettings->password_requires_numbers = $data['password_requires_numbers'];
        $applicationSecuritySettings->password_requires_symbols = $data['password_requires_symbols'];
        $applicationSecuritySettings->session_lifetime = (int) $data['session_lifetime'];
        $applicationSecuritySettings->single_session = $data['single_session'];
        $applicationSecuritySettings->login_rate_limit = (int) $data['login_rate_limit'];
        $applicationSecuritySettings->login_rate_limit_decay = (int) $data['login_rate_limit_decay'];

        $applicationSecuritySettings->save();

        Notification::make()
            ->success()
            ->title('Security settings saved successfully')
            ->send();
    }
}
