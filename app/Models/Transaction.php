<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\TransactionType;


class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'idempotency_key',
        'reversal_of_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class,
            'reversal_of_id'
        );
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(
            Transaction::class,
            'reversal_of_id'
        );
    }
}
