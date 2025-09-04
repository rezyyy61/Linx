<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class EventSettings extends Model
{
    protected $table = 'event_settings';

    protected $fillable = [
        'event_id',
        'type',
        'visibility',
        'join_url',
        'join_platform',
        'join_passcode',
        'join_instructions',
        'join_visible_minutes_before',
        'access_code',
        'og_title',
        'og_description',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
