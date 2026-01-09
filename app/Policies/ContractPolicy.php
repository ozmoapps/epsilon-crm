<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return true;
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->id === $contract->created_by;
    }
}
