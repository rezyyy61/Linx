<?php

namespace App\Services\Campaign\DTOs;

class SignData
{
    public int $campaign_id;

    public string $name;

    public string $email;

    public function __construct(int $campaign_id, string $name, string $email)
    {
        $this->campaign_id = $campaign_id;
        $this->name = $name;
        $this->email = $email;
    }
}
