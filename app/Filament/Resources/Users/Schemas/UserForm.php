<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                    ->dehydrated(fn (?string $state): bool => filled($state)),
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
                Toggle::make('can_access_admin')
                    ->label('Can access admin panel')
                    ->visible(fn (): bool => auth()->user()?->admin_role === 'master'),
                Select::make('admin_role')
                    ->options([
                        'master' => 'Master admin',
                        'admin' => 'Admin (scoped permissions)',
                    ])
                    ->visible(fn (): bool => auth()->user()?->admin_role === 'master'),
                TagsInput::make('admin_permissions')
                    ->placeholder('users, tournaments, leaderboards')
                    ->helperText('Permission keys: users, transactions, leaderboards, tournaments, analytics, settings')
                    ->visible(fn (): bool => auth()->user()?->admin_role === 'master'),
            ]);
    }
}
