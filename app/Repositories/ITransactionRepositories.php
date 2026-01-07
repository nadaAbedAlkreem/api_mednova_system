<?php

namespace App\Repositories;

use App\Models\Transaction;

interface ITransactionRepositories
{
    public function findByMeta(string $key, $value): ?Transaction;

}
