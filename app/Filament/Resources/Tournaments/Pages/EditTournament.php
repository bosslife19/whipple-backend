<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Http\Services\TournamentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTournament extends EditRecord
{
    protected static string $resource = TournamentResource::class;

    protected function getHeaderActions(): array
    {
        $service = app(TournamentService::class);

        return [
            Action::make('importLeaderboards')
                ->label('Import from leaderboards')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    TextInput::make('from_most_frequent')
                        ->numeric()
                        ->default(32)
                        ->required()
                        ->minValue(0)
                        ->maxValue(500),
                    TextInput::make('from_most_wins')
                        ->numeric()
                        ->default(32)
                        ->required()
                        ->minValue(0)
                        ->maxValue(500),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->importFromLeaderboards(
                        $this->record,
                        (int) $data['from_most_frequent'],
                        (int) $data['from_most_wins'],
                        auth()->id()
                    );
                    Notification::make()->title('Players imported')->success()->send();
                    $this->record->refresh();
                }),

            Action::make('addPlayer')
                ->label('Add player')
                ->icon('heroicon-o-user-plus')
                ->form([
                    TextInput::make('identifier')
                        ->label('Email, name, or phone')
                        ->required(),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->addParticipantByIdentifier($this->record, $data['identifier']);
                    Notification::make()->title('Player added')->success()->send();
                }),

            Action::make('createRound')
                ->label('New game lobby / round')
                ->icon('heroicon-o-play')
                ->form([
                    Select::make('game_type')
                        ->options([
                            'quiz' => 'Quiz',
                            'tap_rush' => 'Tap Rush',
                            'math_clash' => 'Math Clash',
                            'color_switch' => 'Color Switch',
                            'defuse_x' => 'Defuse-X',
                        ])
                        ->required(),
                    TextInput::make('round_number')
                        ->numeric()
                        ->minValue(1)
                        ->default(fn () => $this->record->rounds()->max('round_number') + 1),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->createRound(
                        $this->record,
                        $data['game_type'],
                        (int) ($data['round_number'] ?? 1)
                    );
                    Notification::make()->title('Round created')->success()->send();
                }),

            Action::make('startCountdown')
                ->label('Start 20s countdown (latest round)')
                ->icon('heroicon-o-clock')
                ->requiresConfirmation()
                ->action(function () use ($service): void {
                    $round = $this->record->rounds()->orderByDesc('id')->first();
                    if (! $round) {
                        Notification::make()->title('No round yet')->danger()->send();

                        return;
                    }
                    $service->startRoundCountdown($round);
                    Notification::make()->title('Countdown started')->success()->send();
                }),

            Action::make('endRound')
                ->label('Mark game ended')
                ->icon('heroicon-o-stop')
                ->requiresConfirmation()
                ->action(function () use ($service): void {
                    $round = $this->record->rounds()->orderByDesc('id')->first();
                    if (! $round) {
                        Notification::make()->title('No round')->danger()->send();

                        return;
                    }
                    $service->markRoundEnded($round);
                    Notification::make()->title('Round marked ended')->success()->send();
                }),

            Action::make('eliminate')
                ->label('Eliminate bottom N')
                ->icon('heroicon-o-x-circle')
                ->form([
                    TextInput::make('bottom_count')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),
                ])
                ->action(function (array $data) use ($service): void {
                    $n = $service->eliminateBottom($this->record, (int) $data['bottom_count']);
                    Notification::make()->title("Eliminated {$n} players")->success()->send();
                }),

            Action::make('commentaryNormal')
                ->label('Send commentary (blue)')
                ->icon('heroicon-o-chat-bubble-left')
                ->form([
                    Textarea::make('body')->required()->rows(4),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->addCommentary($this->record, auth()->id(), $data['body'], false);
                    Notification::make()->title('Commentary sent')->success()->send();
                }),

            Action::make('commentaryGold')
                ->label('Send KEY MOMENT (gold)')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->form([
                    Textarea::make('body')->required()->rows(4),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->addCommentary($this->record, auth()->id(), $data['body'], true);
                    Notification::make()->title('Key moment sent')->success()->send();
                }),

            Action::make('screenShare')
                ->label('Assign screen-share slot')
                ->icon('heroicon-o-video-camera')
                ->form([
                    TextInput::make('slot')->numeric()->minValue(1)->maxValue(2)->required(),
                    TextInput::make('user_id')->numeric()->required()->label('User ID'),
                ])
                ->action(function (array $data) use ($service): void {
                    $service->setScreenShareSlot(
                        $this->record,
                        (int) $data['slot'],
                        (int) $data['user_id']
                    );
                    Notification::make()->title('Screen share slot updated')->success()->send();
                }),

            Action::make('resetTournament')
                ->label('Reset tournament')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () use ($service): void {
                    $service->resetTournament($this->record);
                    Notification::make()->title('Tournament reset')->success()->send();
                    $this->redirect(TournamentResource::getUrl('index'));
                }),

            DeleteAction::make(),
        ];
    }
}
