<?php

namespace App\Repositories\Eloquent;


use App\Models\Customer;
use App\Models\Wallet;
use App\Repositories\IWalletRepositories;


class WalletRepository  extends BaseRepository implements IWalletRepositories
{
    public function __construct()
    {
        $this->model = new Wallet();
    }
    public function getByOwner($ownerId): Wallet
    {
        return Wallet::where('owner_id',$ownerId )
            ->where('owner_type', Customer::class)
            ->lockForUpdate()
            ->firstOrCreate(['owner_id'   => $ownerId, 'owner_type' => Customer::class]);
    }
    public function increaseAvailableBalance(Wallet $wallet, float $amount): Wallet
    {
        $wallet->available_balance += $amount;
        $wallet->save();
        return $wallet;
    }
}
