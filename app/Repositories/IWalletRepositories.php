<?php

namespace App\Repositories;

use App\Models\Wallet;

interface IWalletRepositories
{
    public function getByOwner($ownerId): Wallet;
    public function increaseAvailableBalance(Wallet $wallet, float $amount);



}
