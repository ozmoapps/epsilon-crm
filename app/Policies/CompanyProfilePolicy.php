<?php

namespace App\Policies;

use App\Models\CompanyProfile;
use App\Models\User;

class CompanyProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CompanyProfile $companyProfile): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CompanyProfile $companyProfile): bool
    {
        return true;
    }

    public function delete(User $user, CompanyProfile $companyProfile): bool
    {
        return true;
    }
}
