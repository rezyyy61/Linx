<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = [
        'title',
        'slug',
        'goal',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'owner_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(CampaignPersona::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(CampaignContent::class);
    }

    public function supporters(): HasMany
    {
        return $this->hasMany(CampaignSupporter::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'running');
    }
}
