<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AdminBalanceAdjustment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustBalances')
                ->label('Adjust wallet / points')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => auth()->user()?->hasAdminPermission('users'))
                ->form([
                    Select::make('kind')
                        ->options([
                            'wallet_add' => 'Add to wallet',
                            'wallet_remove' => 'Remove from wallet',
                            'points_add' => 'Add Whipple points',
                            'points_remove' => 'Remove Whipple points',
                        ])
                        ->required(),
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01),
                    Textarea::make('reason')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $user = $this->record;
                    $admin = auth()->user();

                    DB::transaction(function () use ($data, $user, $admin): void {
                        $kind = $data['kind'];
                        $amount = (float) $data['amount'];

                        match ($kind) {
                            'wallet_add' => $user->increment('wallet_balance', $amount),
                            'wallet_remove' => $user->decrement('wallet_balance', min($amount, max(0, (float) $user->wallet_balance))),
                            'points_add' => $user->increment('whipple_point', $amount),
                            'points_remove' => $user->decrement('whipple_point', min($amount, max(0, (float) $user->whipple_point))),
                        };

                        AdminBalanceAdjustment::query()->create([
                            'user_id' => $user->id,
                            'admin_user_id' => $admin->id,
                            'kind' => $kind,
                            'amount' => $amount,
                            'reason' => $data['reason'] ?? null,
                        ]);

                        $user->refresh();
                    });

                    Notification::make()->title('Balances updated')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
