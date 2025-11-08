<?php

namespace App\Policies;

use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransactionItem $transactionItem): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TransactionItem $transactionItem): bool
    {
        return true;
    }

    public function delete(User $user, TransactionItem $transactionItem): bool
    {
        return true;
    }

    public function restore(User $user, TransactionItem $transactionItem): bool
    {
        return true;
    }

    public function forceDelete(User $user, TransactionItem $transactionItem): bool
    {
        return true;
    }
}
