<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Widgets\UserOverviewWidget;
use App\Models\User;
use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->components([
                        FlexTextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        FlexTextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        FlexTextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->confirmed()
                            ->maxLength(255),
                        FlexTextInput::make('password_confirmation')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->visible(fn (string $context): bool => $context === 'create' || $context === 'edit'),
                        SelectField::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),

                Section::make('Security')
                    ->components([
                        FlexDateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('Leave empty to keep unverified.'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    #[\Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->components([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable(),
                        TextEntry::make('roles.name')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin' => 'danger',
                                'user' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Security Status')
                    ->components([
                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime()
                            ->placeholder('Unverified')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('app_authentication_secret')
                            ->label('App MFA')
                            ->formatStateUsing(fn ($state): string => filled($state) ? 'Enabled' : 'Disabled')
                            ->badge()
                            ->color(fn ($state): string => filled($state) ? 'success' : 'warning'),
                        TextEntry::make('has_email_authentication')
                            ->label('Email MFA')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Enabled' : 'Disabled')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                    ])->columns(2),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (User $user): string => 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=D97706&background=FEF3C7')
                    ->grow(false),

                TextColumn::make('name')
                    ->label('User')
                    ->description(fn (User $user): string => $user->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('semibold')
                    ->copyable()
                    ->copyableState(fn (User $user): string => $user->email)
                    ->copyMessage('Email copied to clipboard'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label('Email Status')
                    ->badge()
                    ->state(fn (User $user): string => $user->email_verified_at ? 'Verified' : 'Unverified')
                    ->color(fn (string $state): string => $state === 'Verified' ? 'success' : 'danger')
                    ->icon(fn (string $state): string => $state === 'Verified' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->sortable(),

                TextColumn::make('mfa_status')
                    ->label('Security')
                    ->badge()
                    ->state(function (User $user): string {
                        if ($user->app_authentication_secret && $user->has_email_authentication) {
                            return 'App & Email';
                        }
                        if ($user->app_authentication_secret) {
                            return 'Authenticator';
                        }
                        if ($user->has_email_authentication) {
                            return 'Email OTP';
                        }
                        if ($user->getAttribute('two_factor_secret')) {
                            return 'Fortify 2FA';
                        }
                        if ($user->passkeys()->exists()) {
                            return 'Passkey';
                        }

                        return 'None';
                    })
                    ->color(fn (string $state): string => $state === 'None' ? 'gray' : 'info')
                    ->icon(fn (string $state): string => $state === 'None' ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->description(fn (User $user): string => $user->created_at?->diffForHumans() ?? '')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),

                Filter::make('has_mfa')
                    ->label('MFA Enabled')
                    ->query(fn (Builder $builder) => $builder->where(function ($q): void {
                        $q->whereNotNull('app_authentication_secret')
                            ->orWhere('has_email_authentication', true)
                            ->orWhereNotNull('two_factor_secret')
                            ->orWhereHas('passkeys');
                    })),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('impersonate')
                        ->label('Impersonate')
                        ->icon('heroicon-m-arrow-right-on-rectangle')
                        ->color('warning')
                        ->url(fn (User $user): string => route('impersonate', $user->id))
                        ->openUrlInNewTab()
                        ->visible(fn (User $record): bool => ($user = Auth::user()) instanceof User && $user->hasRole('admin') && $record->canBeImpersonated()),

                    Action::make('verify_email')
                        ->icon('heroicon-m-check-badge')
                        ->label('Verify Email')
                        ->action(function (User $user): void {
                            $user->forceFill(['email_verified_at' => now()])->save();
                            Notification::make()
                                ->title('Email Verified')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (User $user): bool => $user->email_verified_at === null)
                        ->requiresConfirmation()
                        ->color('success'),

                    Action::make('unverify_email')
                        ->icon('heroicon-m-no-symbol')
                        ->label('Revoke Verification')
                        ->action(function (User $user): void {
                            $user->forceFill(['email_verified_at' => null])->save();
                            Notification::make()
                                ->title('Verification Revoked')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (User $user): bool => $user->email_verified_at !== null)
                        ->requiresConfirmation()
                        ->color('danger'),

                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->hidden(fn (User $user): bool => $user->id === Auth::id()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('verify_selected')
                        ->label('Verify Selected')
                        ->icon('heroicon-m-check-badge')
                        ->action(function ($records): void {
                            $records->each->forceFill(['email_verified_at' => now()])->each->save();
                            Notification::make()
                                ->title('Selected Users Verified')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    BulkAction::make('unverify_selected')
                        ->label('Unverify Selected')
                        ->icon('heroicon-m-no-symbol')
                        ->action(function ($records): void {
                            $records->each->forceFill(['email_verified_at' => null])->each->save();
                            Notification::make()
                                ->title('Selected Users Unverified')
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->color('danger'),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('Create a new user or adjust your search filters.');
    }

    #[\Override]
    public static function getWidgets(): array
    {
        return [
            UserOverviewWidget::class,
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
