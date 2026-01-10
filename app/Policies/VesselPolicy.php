<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vessel;

class VesselPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vessel $vessel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Vessel $vessel): bool
    {
        return true;
    }

    public function delete(User $user, Vessel $vessel): bool
    {
        return true;
    }
}
