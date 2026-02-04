<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->confirmed()
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->visible(fn (string $context): bool => $context === 'create' || $context === 'edit'),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),

                Section::make('Security')
                    ->components([
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('Leave empty to keep unverified.'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

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

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->colors([
                        'success' => fn ($state): bool => $state !== null,
                        'danger' => fn ($state): bool => $state === null,
                    ])
                    ->sortable(),
                IconColumn::make('app_authentication_secret')
                    ->label('App MFA')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->colors([
                        'success' => fn ($state): bool => filled($state),
                        'warning' => fn ($state): bool => blank($state),
                    ])
                    ->sortable(),
                IconColumn::make('has_email_authentication')
                    ->label('Email MFA')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-envelope-open')
                    ->colors([
                        'success' => fn (bool $state): bool => $state,
                        'warning' => fn (bool $state): bool => ! $state,
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
                TernaryFilter::make('app_authentication_secret')
                    ->label('App MFA Enabled')
                    ->nullable(),
                TernaryFilter::make('has_email_authentication')
                    ->label('Email MFA Enabled')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user-plus')
                    ->url(fn (User $user): string => route('impersonate', $user->id))
                    ->openUrlInNewTab()
                    ->visible(fn (User $record): bool => ($user = Auth::user()) instanceof User && $user->hasRole('admin') && $record->canBeImpersonated()),
                ViewAction::make(),
                EditAction::make(),
                Action::make('verify_email')
                    ->icon('heroicon-o-check')
                    ->label('Verify')
                    ->action(fn (User $user) => $user->forceFill(['email_verified_at' => now()])->save())
                    ->visible(fn (User $user): bool => $user->email_verified_at === null)
                    ->requiresConfirmation()
                    ->color('success'),
                Action::make('unverify_email')
                    ->icon('heroicon-o-x-mark')
                    ->label('Unverify')
                    ->action(fn (User $user) => $user->forceFill(['email_verified_at' => null])->save())
                    ->visible(fn (User $user): bool => $user->email_verified_at !== null)
                    ->requiresConfirmation()
                    ->color('danger'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('verify_selected')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->forceFill(['email_verified_at' => now()])->each->save())
                        ->requiresConfirmation()
                        ->color('success'),
                    BulkAction::make('unverify_selected')
                        ->label('Unverify Selected')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->forceFill(['email_verified_at' => null])->each->save())
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ]);
    }

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
