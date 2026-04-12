<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->default('Whipple Tournament')
                    ->maxLength(255),
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
