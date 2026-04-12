<?php

namespace App\Filament\Resources\LeaderboardPeriods\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaderboardPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('label'),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime(),
                IconColumn::make('is_current')->boolean()->label('Current'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }
}
