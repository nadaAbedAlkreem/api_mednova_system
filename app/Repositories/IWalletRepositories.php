<?php

namespace App\Repositories;

use App\Models\Wallet;

interface IWalletRepositories
{
    public function getByOwner($owner): Wallet;
    public function increaseAvailableBalance(Wallet $wallet, float $amount);



}
