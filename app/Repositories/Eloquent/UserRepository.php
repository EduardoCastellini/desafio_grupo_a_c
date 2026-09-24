<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class UserRepository implements UserRepositoryInterface
{
    /**
     * Create a user and its wallet atomically.
     *
     * @param  array<string, mixed>  $userData
     */
    public function createWithWallet(array $userData): User
    {
        return DB::transaction(function () use ($userData): User {
            $user = User::query()->create($userData);

            $user->wallet()->create([
                'wallet_transaction_key' => $user->id.'-'.uniqid(),
                'balance' => 0,
            ]);

            return $user;
        });
    }
}
