<?php

namespace App\Filament\Resources\LeaderboardPeriods;

use App\Filament\Resources\LeaderboardPeriods\Pages\ListLeaderboardPeriods;
use App\Filament\Resources\LeaderboardPeriods\Tables\LeaderboardPeriodsTable;
use App\Models\LeaderboardPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaderboardPeriodResource extends Resource
{
    protected static ?string $model = LeaderboardPeriod::class;

    protected static ?string $navigationLabel = 'Leaderboard periods';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Tournament & Leaderboards';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return LeaderboardPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaderboardPeriods::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAdminPermission('leaderboards') ?? false;
    }
}
