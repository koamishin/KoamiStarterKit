<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Components\AuthLayoutRadio;
use App\Settings\ApplicationFeaturesSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
class ApplicationFeaturesSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected string $view = 'filament.clusters.settings.pages.application-features-settings-page';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Application Features';

    protected static ?string $navigationLabel = 'Application Features';

    public ?array $data = [];

    public function mount(): void
    {
        $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);

        $this->form->fill([
            'registration_enabled' => $applicationFeaturesSettings->registration_enabled,
            'email_verification_required' => $applicationFeaturesSettings->email_verification_required,
            'two_factor_authentication_enabled' => $applicationFeaturesSettings->two_factor_authentication_enabled,
            'password_reset_enabled' => $applicationFeaturesSettings->password_reset_enabled,
            'user_impersonation_enabled' => $applicationFeaturesSettings->user_impersonation_enabled,
            'default_user_role' => $applicationFeaturesSettings->default_user_role,
            'activity_log_enabled' => $applicationFeaturesSettings->activity_log_enabled,
            'notifications_enabled' => $applicationFeaturesSettings->notifications_enabled,
            'auth_layout' => $applicationFeaturesSettings->auth_layout,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Authentication Features')
                        ->description('Configure user authentication options.')
                        ->schema([
                            Toggle::make('registration_enabled')
                                ->label('Enable User Registration')
                                ->default(true),
                            Toggle::make('email_verification_required')
                                ->label('Require Email Verification')
                                ->default(true),
                            Toggle::make('two_factor_authentication_enabled')
                                ->label('Enable Two-Factor Authentication')
                                ->default(true),
                            Toggle::make('password_reset_enabled')
                                ->label('Enable Password Reset')
                                ->default(true),
                        ])
                        ->columns(2),

                    Section::make('Authentication UI')
                        ->description('Configure the appearance of authentication pages.')
                        ->schema([
                            AuthLayoutRadio::make('auth_layout')
                                ->label('Authentication Layout')
                                ->default('simple')
                                ->required(),
                        ])
                        ->columns(1),

                    Section::make('User Management')
                        ->description('Configure user management features.')
                        ->schema([
                            Toggle::make('user_impersonation_enabled')
                                ->label('Enable User Impersonation')
                                ->default(true),
                            Select::make('default_user_role')
                                ->label('Default Role for New Users')
                                ->options($this->getRoleOptions())
                                ->nullable()
                                ->searchable(),
                        ])
                        ->columns(2),

                    Section::make('System Features')
                        ->description('Configure system-level features.')
                        ->schema([
                            Toggle::make('activity_log_enabled')
                                ->label('Enable Activity Logging')
                                ->default(true),
                            Toggle::make('notifications_enabled')
                                ->label('Enable Notifications')
                                ->default(true),
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

        $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);

        $applicationFeaturesSettings->registration_enabled = $data['registration_enabled'];
        $applicationFeaturesSettings->email_verification_required = $data['email_verification_required'];
        $applicationFeaturesSettings->two_factor_authentication_enabled = $data['two_factor_authentication_enabled'];
        $applicationFeaturesSettings->password_reset_enabled = $data['password_reset_enabled'];
        $applicationFeaturesSettings->user_impersonation_enabled = $data['user_impersonation_enabled'];
        $applicationFeaturesSettings->default_user_role = $data['default_user_role'];
        $applicationFeaturesSettings->activity_log_enabled = $data['activity_log_enabled'];
        $applicationFeaturesSettings->notifications_enabled = $data['notifications_enabled'];
        $applicationFeaturesSettings->auth_layout = $data['auth_layout'];

        $applicationFeaturesSettings->save();

        Notification::make()
            ->success()
            ->title('Application features saved successfully')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getRoleOptions(): array
    {
        return \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray();
    }
}
