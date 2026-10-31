<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * @property-read Schema $form
 */
class MailSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected string $view = 'filament.clusters.settings.pages.mail-settings-page';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Mail Delivery';

    protected static ?string $navigationLabel = 'Mail Delivery';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mailer' => (string) config('mail.default', 'smtp'),
            'host' => (string) config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => (string) config('mail.mailers.smtp.port', 2525),
            'encryption' => (string) (config('mail.mailers.smtp.encryption') ?? 'none'),
            'from_address' => (string) config('mail.from.address', 'hello@example.com'),
            'from_name' => (string) config('mail.from.name', config('app.name')),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Active Mail Configuration')
                        ->description('Current mailer parameters loaded from the environment (.env).')
                        ->icon('heroicon-o-server-stack')
                        ->schema([
                            TextInput::make('mailer')
                                ->label('Mail Driver / Transport')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('host')
                                ->label('SMTP Host')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('port')
                                ->label('SMTP Port')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('encryption')
                                ->label('Encryption Protocol')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('from_address')
                                ->label('Default Sender Email (From Address)')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('from_name')
                                ->label('Default Sender Name (From Name)')
                                ->disabled()
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                    Section::make('Deliverability & Verification')
                        ->description('Send a test message using the configured mail transport to verify SMTP handshake and inbox delivery.')
                        ->icon('heroicon-o-paper-airplane')
                        ->schema([])
                        ->footer([
                            Actions::make([
                                Action::make('testMail')
                                    ->label('Send Test Email')
                                    ->icon('heroicon-m-paper-airplane')
                                    ->color('primary')
                                    ->schema([
                                        TextInput::make('recipient')
                                            ->label('Recipient Email')
                                            ->email()
                                            ->required()
                                            ->default(function (): string {
                                                /** @var User|null $user */
                                                $user = Filament::auth()->user();

                                                return $user?->email ?? 'admin@example.com';
                                            }),
                                    ])
                                    ->action(function (array $data): void {
                                        $recipient = $data['recipient'];

                                        try {
                                            Mail::raw(
                                                'This is an automated test message from '.config('app.name').' verifying that mail deliverability is operational.',
                                                function ($message) use ($recipient): void {
                                                    $message->to($recipient)
                                                        ->subject('['.config('app.name').'] Mail Delivery Verification Test');
                                                }
                                            );

                                            Notification::make()
                                                ->title('Test Email Sent')
                                                ->body("Successfully dispatched test email to {$recipient}.")
                                                ->success()
                                                ->send();
                                        } catch (Throwable $e) {
                                            Notification::make()
                                                ->title('Mail Delivery Failed')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->persistent()
                                                ->send();
                                        }
                                    }),
                            ]),
                        ]),
                ]),
            ])
            ->statePath('data');
    }
}
