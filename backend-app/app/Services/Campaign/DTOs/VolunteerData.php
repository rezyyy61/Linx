<?php

namespace App\Services\Campaign\DTOs;

class VolunteerData
{
    public int $campaign_id;

    public string $name;

    public string $email;

    public ?string $role;

    public function __construct(int $campaign_id, string $name, string $email, ?string $role = null)
    {
        $this->campaign_id = $campaign_id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }
}
