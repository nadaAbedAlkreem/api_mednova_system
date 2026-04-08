<?php

namespace App\Repositories\Eloquent;


use App\Models\Transaction;
use App\Repositories\ITransactionRepositories;


class TransactionRepository  extends BaseRepository implements ITransactionRepositories
{
    public function __construct()
    {
        $this->model = new Transaction();
    }

}
