<?php

namespace App\Repositories\Eloquent;

use App\Models\LedgerEntry;
use App\Repositories\Contracts\LedgerEntryRepositoryInterface;

final class LedgerEntryRepository implements LedgerEntryRepositoryInterface
{
    public function create(array $data): LedgerEntry
    {
        return LedgerEntry::query()->create($data);
    }

    public function createMany(array $entries): void
    {
        LedgerEntry::query()->insert($entries);
    }
}