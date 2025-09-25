<?php

namespace App\Services\Omnix;


use App\Models\Customer;
use App\Models\OmnixLog;
use App\Repositories\IOmnixNotificationRepositories;

class NotificationManager
{
    protected array $drivers = [];

    public function registerDriver(string $name, IOmnixNotificationRepositories $driver)
    {
        $this->drivers[$name] = $driver;
    }

    public function send(string $driver, Customer $customer, string $template, array $params = []): array
    {
        if (!isset($this->drivers[$driver])) {
            throw new \Exception("Notification driver [$driver] not found.");
        }

        return $this->drivers[$driver]->send($customer, $template, $params);
    }
}
