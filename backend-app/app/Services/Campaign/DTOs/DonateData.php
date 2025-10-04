<?php

namespace App\Services\Campaign\DTOs;

class DonateData
{
    public int $campaign_id;

    public string $requester_email;

    public float $amount;

    public ?string $currency;

    public ?string $message;

    public ?string $requester_name;

    public function __construct(
        int $campaign_id,
        string $requester_email,
        float $amount,
        ?string $currency = null,
        ?string $message = null,
        ?string $requester_name = null
    ) {
        $this->campaign_id = $campaign_id;
        $this->requester_email = $requester_email;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->message = $message;
        $this->requester_name = $requester_name;
    }
}
