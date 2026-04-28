<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('otp')
                    ->numeric(),
                Textarea::make('pin')
                    ->columnSpanFull(),
                TextInput::make('referral_code'),
                TextInput::make('referred_by')
                    ->numeric(),
                TextInput::make('wallet_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('whipple_point')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
