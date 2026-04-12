<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => $q->with('user'))
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->numeric(),
                TextColumn::make('status')->badge(),
                TextColumn::make('gateway')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')->limit(40),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }
}
