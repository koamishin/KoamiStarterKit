<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FlexTextInput::make('name')
                    ->required(),
                FlexTextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                FlexDateTimePicker::make('email_verified_at'),
                FlexTextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
