<?php

namespace App\Repositories;

use App\Models\Wallet;

interface ICardRepositories
{
    public function storeFromGateway($owner, array $cardData);

}
