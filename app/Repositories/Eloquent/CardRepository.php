<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Card;
use App\Models\Report;
use App\Repositories\IAdminRepositories;
use App\Repositories\IAuthRepositories;
use App\Repositories\ICardRepositories;
use App\Repositories\IReportRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class CardRepository extends BaseRepository implements ICardRepositories
{
    public function __construct()
    {
        $this->model = new Card();
    }
    public function storeFromGateway($owner, array $cardData): Card
    {
        return Card::create([
            'owner_card_type' => get_class($owner),
            'owner_card_id' => $owner->id,
            'gateway' => $cardData['gateway'] ?? 'amwal',
            'card_token' => $cardData['token'] ?? null,
            'card_brand' => $cardData['brand'] ?? null,
            'card_last4' => $cardData['last4'] ?? null,
            'card_country' => $cardData['country'] ?? null,
            'expiry_month' => $cardData['expiry_month'] ?? null,
            'expiry_year' => $cardData['expiry_year'] ?? null,
            'is_default' => true,
            'status' => 'active',
        ]);
    }
}
