<?php

namespace App\Repositories;

use App\Models\Customer;

interface IOmnixNotificationRepositories
{

    public function send(Customer $customer, string $template, array $params = []): array;


}
