<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Someone who wants an email when a box they drew has an unusual fire week. */
class AlertSubscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_alert_week' => 'date', 'last_sent_at' => 'datetime', 'west' => 'float', 'south' => 'float', 'east' => 'float', 'north' => 'float'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $s) => $s->token ??= Str::random(40));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function bbox(): array
    {
        return [$this->west, $this->south, $this->east, $this->north];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'bbox' => $this->bbox(),
            'last_alert_week' => $this->last_alert_week?->toDateString(),
            'last_sent_at' => $this->last_sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
