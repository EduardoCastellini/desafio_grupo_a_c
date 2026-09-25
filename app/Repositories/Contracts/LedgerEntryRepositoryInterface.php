<?php

namespace App\Repositories\Contracts;

use App\Models\LedgerEntry;

interface LedgerEntryRepositoryInterface
{
    public function create(array $data): LedgerEntry;

    public function createMany(array $entries): void;
}
