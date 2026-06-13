<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\ApplicationDetailsSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
            'site_logo_path' => $applicationDetailsSettings->site_logo_path,
            'site_favicon_path' => $applicationDetailsSettings->site_favicon_path,
            'site_logo_url' => $applicationDetailsSettings->site_logo_url,
            'site_favicon_url' => $applicationDetailsSettings->site_favicon_url,
            'timezone' => $applicationDetailsSettings->timezone,
            'date_format' => $applicationDetailsSettings->date_format,
            'time_format' => $applicationDetailsSettings->time_format,
            'contact_email' => $applicationDetailsSettings->contact_email,
            'support_url' => $applicationDetailsSettings->support_url,
            'support_phone' => $applicationDetailsSettings->support_phone,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Site Identity')
                        ->description('Tell people who you are. This is the name and short pitch visitors will see.')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            TextInput::make('site_name')
                                ->label('Site Name')
                                ->helperText('The public name of your application (e.g. "Acme Inc.").')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Textarea::make('site_description')
                                ->label('Site Description')
                                ->helperText('A short description used in meta tags and search results (max 500 characters).')
                                ->required()
                                ->rows(3)
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),

                    Section::make('Branding')
                        ->description('Upload your logo and favicon. SVG, PNG, JPG and ICO formats are supported.')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('site_logo_path')
                                ->label('Site Logo')
                                ->helperText('Recommended size: 256x64px. Transparent background works best.')
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatios([
                                    '16:5',
                                    '4:1',
                                    '1:1',
                                ])
                                ->disk('public')
                                ->directory('branding')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                                ->maxSize(2048)
                                ->downloadable()
                                ->openable()
                                ->columnSpan(1),

                            FileUpload::make('site_favicon_path')
                                ->label('Favicon')
                                ->helperText('Recommended size: 32x32px or 64x64px. ICO, PNG or SVG.')
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('branding')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml', 'image/jpeg'])
                                ->maxSize(512)
                                ->downloadable()
                                ->openable()
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                    Section::make('Localization')
                        ->description('Set your timezone and how dates and times are displayed across the app.')
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            Select::make('timezone')
                                ->label('Timezone')
                                ->helperText('All times across the application will be displayed in this timezone.')
                                ->required()
                                ->searchable()
                                ->options($this->getTimezoneOptions())
                                ->native(false),

                            Select::make('date_format')
                                ->label('Date Format')
                                ->helperText(fn (Get $get): ?string => $this->formatDatePreview($get('date_format')))
                                ->required()
                                ->options($this->getDateFormatOptions())
                                ->searchable()
                                ->native(false),

                            Select::make('time_format')
                                ->label('Time Format')
                                ->helperText(fn (Get $get): ?string => $this->formatTimePreview($get('time_format')))
                                ->required()
                                ->options($this->getTimeFormatOptions())
                                ->searchable()
                                ->native(false),
                        ])
                        ->columns(3),

                    Section::make('Contact & Support')
                        ->description('How can users reach you? These details appear in footers and support pages.')
                        ->icon('heroicon-o-lifebuoy')
                        ->schema([
                            TextInput::make('contact_email')
                                ->label('Contact Email')
                                ->helperText('The public email address shown to users for general inquiries.')
                                ->email()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('support_phone')
                                ->label('Support Phone')
                                ->helperText('Optional phone number for support (include country code).')
                                ->tel()
                                ->maxLength(30)
                                ->columnSpan(1),

                            TextInput::make('support_url')
                                ->label('Support URL')
                                ->helperText('Link to your help center, documentation, or contact form.')
                                ->url()
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Section::make('Advanced')
                        ->description('Additional configuration options.')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('site_logo_url')
                                ->label('Logo URL (override)')
                                ->helperText('Optional. If set, this URL overrides the uploaded logo (useful for CDNs).')
                                ->url()
                                ->maxLength(500)
                                ->columnSpan(1),

                            TextInput::make('site_favicon_url')
                                ->label('Favicon URL (override)')
                                ->helperText('Optional. If set, this URL overrides the uploaded favicon (useful for CDNs).')
                                ->url()
                                ->maxLength(500)
                                ->columnSpan(1),
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
        $applicationDetailsSettings->site_logo_path = $data['site_logo_path'] ?? null;
        $applicationDetailsSettings->site_favicon_path = $data['site_favicon_path'] ?? null;
        $applicationDetailsSettings->site_logo_url = $data['site_logo_url'] ?? null;
        $applicationDetailsSettings->site_favicon_url = $data['site_favicon_url'] ?? null;
        $applicationDetailsSettings->timezone = $data['timezone'];
        $applicationDetailsSettings->date_format = $data['date_format'];
        $applicationDetailsSettings->time_format = $data['time_format'];
        $applicationDetailsSettings->contact_email = $data['contact_email'] ?? null;
        $applicationDetailsSettings->support_url = $data['support_url'] ?? null;
        $applicationDetailsSettings->support_phone = $data['support_phone'] ?? null;

        $applicationDetailsSettings->save();

        Notification::make()
            ->success()
            ->title('Application details saved successfully')
            ->send();
    }

    /**
     * Group timezones by region for the searchable select.
     *
     * @return array<string, array<string, string>>
     */
    protected function getTimezoneOptions(): array
    {
        $grouped = [];

        foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::ALL) as $timezone) {
            $parts = explode('/', $timezone, 2);
            $region = $parts[0] ?? 'Other';
            $grouped[$region][$timezone] = str_replace('_', ' ', $timezone);
        }

        ksort($grouped);

        foreach (array_keys($grouped) as $region) {
            asort($grouped[$region]);
        }

        return $grouped;
    }

    /**
     * @return array<string, string>
     */
    protected function getDateFormatOptions(): array
    {
        $examples = [
            'Y-m-d' => '2025-12-31',
            'd/m/Y' => '31/12/2025',
            'm/d/Y' => '12/31/2025',
            'd M Y' => '31 Dec 2025',
            'M d, Y' => 'Dec 31, 2025',
            'd F Y' => '31 December 2025',
            'F d, Y' => 'December 31, 2025',
            'D, M d Y' => 'Wed, Dec 31 2025',
            'l, F d Y' => 'Wednesday, December 31 2025',
            'd-m-Y' => '31-12-2025',
            'Y/m/d' => '2025/12/31',
            'd.m.Y' => '31.12.2025',
        ];

        $options = [];
        foreach ($examples as $format => $example) {
            $options[$format] = "{$format}  (e.g. {$example})";
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected function getTimeFormatOptions(): array
    {
        $examples = [
            'H:i:s' => '23:59:59',
            'H:i' => '23:59',
            'g:i a' => '11:59 pm',
            'g:i A' => '11:59 PM',
            'h:i a' => '11:59 pm',
            'h:i A' => '11:59 PM',
        ];

        $options = [];
        foreach ($examples as $format => $example) {
            $options[$format] = "{$format}  (e.g. {$example})";
        }

        return $options;
    }

    protected function formatDatePreview(?string $format): ?string
    {
        if (in_array($format, [null, '', '0'], true)) {
            return null;
        }

        try {
            return 'Preview: '.now()->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function formatTimePreview(?string $format): ?string
    {
        if (in_array($format, [null, '', '0'], true)) {
            return null;
        }

        try {
            return 'Preview: '.now()->format($format);
        } catch (\Throwable) {
            return null;
        }
    }
}
