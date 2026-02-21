<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\ApplicationDetailsSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
class ApplicationDetailsSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected string $view = 'filament.clusters.settings.pages.application-details-settings-page';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Application Details';

    protected static ?string $navigationLabel = 'Application Details';

    public ?array $data = [];

    public function mount(): void
    {
        $applicationDetailsSettings = app(ApplicationDetailsSettings::class);

        $this->form->fill([
            'site_name' => $applicationDetailsSettings->site_name,
            'site_description' => $applicationDetailsSettings->site_description,
            'site_logo_url' => $applicationDetailsSettings->site_logo_url,
            'site_favicon_url' => $applicationDetailsSettings->site_favicon_url,
            'timezone' => $applicationDetailsSettings->timezone,
            'date_format' => $applicationDetailsSettings->date_format,
            'time_format' => $applicationDetailsSettings->time_format,
            'contact_email' => $applicationDetailsSettings->contact_email,
            'support_url' => $applicationDetailsSettings->support_url,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Site Information')
                        ->description('Configure your application\'s basic information.')
                        ->schema([
                            TextInput::make('site_name')
                                ->label('Site Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('site_description')
                                ->label('Site Description')
                                ->required()
                                ->maxLength(500),
                            TextInput::make('site_logo_url')
                                ->label('Logo URL')
                                ->url()
                                ->nullable()
                                ->maxLength(500),
                            TextInput::make('site_favicon_url')
                                ->label('Favicon URL')
                                ->url()
                                ->nullable()
                                ->maxLength(500),
                        ])
                        ->columns(2),

                    Section::make('Date & Time')
                        ->description('Configure how dates and times are displayed.')
                        ->schema([
                            TextInput::make('timezone')
                                ->label('Timezone')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('date_format')
                                ->label('Date Format')
                                ->required()
                                ->maxLength(20),
                            TextInput::make('time_format')
                                ->label('Time Format')
                                ->required()
                                ->maxLength(20),
                        ])
                        ->columns(3),

                    Section::make('Contact Information')
                        ->description('Configure contact and support details.')
                        ->schema([
                            TextInput::make('contact_email')
                                ->label('Contact Email')
                                ->email()
                                ->nullable()
                                ->maxLength(255),
                            TextInput::make('support_url')
                                ->label('Support URL')
                                ->url()
                                ->nullable()
                                ->maxLength(500),
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

        $applicationDetailsSettings = app(ApplicationDetailsSettings::class);

        $applicationDetailsSettings->site_name = $data['site_name'];
        $applicationDetailsSettings->site_description = $data['site_description'];
        $applicationDetailsSettings->site_logo_url = $data['site_logo_url'];
        $applicationDetailsSettings->site_favicon_url = $data['site_favicon_url'];
        $applicationDetailsSettings->timezone = $data['timezone'];
        $applicationDetailsSettings->date_format = $data['date_format'];
        $applicationDetailsSettings->time_format = $data['time_format'];
        $applicationDetailsSettings->contact_email = $data['contact_email'];
        $applicationDetailsSettings->support_url = $data['support_url'];

        $applicationDetailsSettings->save();

        Notification::make()
            ->success()
            ->title('Application details saved successfully')
            ->send();
    }
}
