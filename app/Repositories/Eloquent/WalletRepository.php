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

}
