<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Models\Wallet;
use App\Repositories\IWalletRepositories;

class WalletRepository extends BaseRepository implements IWalletRepositories
{
    public function __construct()
    {
        $this->model = new Wallet();
    }

    public function getByOwner($ownerId): Wallet
    {
        return Wallet::where('owner_id', $ownerId)
            ->where('owner_type', Customer::class)
            ->lockForUpdate()
            ->firstOrCreate(['owner_id' => $ownerId, 'owner_type' => Customer::class]);
    }
    public function findByOwnerForUpdate($ownerId): ?Wallet
    {
        return Wallet::where('owner_id', $ownerId)
            ->where('owner_type', Customer::class)
            ->lockForUpdate()
            ->first();
    }
    public function getPlatformWallet(): Wallet
    {
        Wallet::firstOrCreate(
            [
                'owner_type' => 'platform',
                'owner_id' => 1,
                'currency' => 'OMR',
            ],
            [   'available_balance' => 0,
                'pending_balance' => 0,
                'frozen_balance' => 0]);
        return Wallet::query()
            ->where('owner_type', 'platform')
            ->where('owner_id', 1)
            ->where('currency', 'OMR')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function getOrCreateByOwnerForUpdate($ownerId, ?string $currency = null): Wallet
    {
        return Wallet::where('owner_id', $ownerId)
            ->where('owner_type', Customer::class)
            ->lockForUpdate()
            ->firstOrCreate(
                ['owner_id' => $ownerId, 'owner_type' => Customer::class],
                [
                    'currency' => $currency ?? 'OMR',
                    'available_balance' => 0,
                    'pending_balance' => 0,
                    'frozen_balance' => 0,
                ]
            );
    }

    public function increaseAvailableBalance(Wallet $wallet, float $amount): Wallet
    {
        $wallet->increment('available_balance', $amount);

        return $wallet->refresh();
    }

    public function increasePendingBalance(Wallet $wallet, float $amount): Wallet
    {
        $wallet->increment('pending_balance', $amount);

        return $wallet->refresh();
    }


    public function getPlatformWalletReadOnlyInRepo(): Wallet
    {
        return Wallet::where('owner_type', 'platform')
            ->where('owner_id', 1)
            ->where('currency', 'OMR')
            ->firstOrFail();
    }
}
