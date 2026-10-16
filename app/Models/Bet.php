<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    public const UP = 'up';
    public const DOWN = 'down';

    public const PENDING = 'pending';
    public const WON = 'won';
    public const LOST = 'lost';

    protected $fillable = [
        'stake_cents', 'direction', 'symbol',
        'entry_quote', 'entry_epoch',
        'exit_quote', 'exit_epoch', 'ticks',
        'status', 'profit_cents',
    ];

    protected $casts = [
        'stake_cents' => 'integer',
        'entry_quote' => 'float',
        'entry_epoch' => 'integer',
        'exit_quote' => 'float',
        'exit_epoch' => 'integer',
        'ticks' => 'array',
        'profit_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
