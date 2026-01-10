<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuotePolicy
{
    public function view(User $user, Quote $quote): bool
    {
        return true;
    }

    public function update(User $user, Quote $quote): Response
    {
        return $quote->isLocked()
            ? Response::deny('Bu teklif siparişe dönüştürüldüğü için düzenlenemez.')
            : Response::allow();
    }

    public function delete(User $user, Quote $quote): Response
    {
        return $quote->isLocked()
            ? Response::deny('Bu teklifin bağlı siparişi olduğu için silinemez.')
            : Response::allow();
    }
}
