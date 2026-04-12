<?php

namespace App\Filament\Resources\LeaderboardPeriods\Pages;

use App\Filament\Resources\LeaderboardPeriods\LeaderboardPeriodResource;
use App\Http\Services\LeaderboardCalculationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLeaderboardPeriods extends ListRecords
{
    protected static string $resource = LeaderboardPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetWeeklyLeaderboard')
                ->label('Reset weekly leaderboard')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('label')->maxLength(255)->label('Optional label for new period'),
                ])
                ->action(function (?array $data): void {
                    $service = app(LeaderboardCalculationService::class);
                    $service->resetWeeklyPeriod($data['label'] ?? null);
                    Notification::make()
                        ->title('Leaderboard period reset — new weekly window started')
                        ->success()
                        ->send();
                }),
        ];
    }
}
