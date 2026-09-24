<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Create a user and its wallet atomically.
     *
     * @param  array<string, mixed>  $userData
     */
    public function createWithWallet(array $userData): User;
}
