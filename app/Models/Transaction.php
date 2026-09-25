<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property TransactionType $type
 * @property int $amount
 * @property string $idempotency_key
 * @property int|null $reversal_of_id
 */
#[Fillable(['user_id', 'type', 'amount', 'idempotency_key', 'reversal_of_id'])]
class Transaction extends Model
{
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
