<?php

namespace App\Repositories\Eloquent;


use App\Models\Wallet;
use App\Repositories\IWalletRepositories;


class WalletRepository  extends BaseRepository implements IWalletRepositories
{
    public function __construct()
    {
        $this->model = new Wallet();
    }
    public function getByOwner($owner): Wallet
    {
        return Wallet::where('owner_id', $owner->id)
            ->where('owner_type', get_class($owner))
            ->lockForUpdate() // 🔒 مهم جداً للعمليات المالية
            ->firstOrCreate();
    }
    public function increaseAvailableBalance(Wallet $wallet, float $amount): Wallet
    {
        $wallet->available_balance += $amount;
        $wallet->save();

        return $wallet;
    }
}
