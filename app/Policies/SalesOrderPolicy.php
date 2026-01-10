<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SalesOrderPolicy
{
    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return true;
    }

    public function update(User $user, SalesOrder $salesOrder): Response
    {
        return $salesOrder->isLocked()
            ? Response::deny('Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.')
            : Response::allow();
    }

    public function delete(User $user, SalesOrder $salesOrder): Response
    {
        return $salesOrder->isLocked()
            ? Response::deny('Bu siparişin bağlı sözleşmesi olduğu için silinemez.')
            : Response::allow();
    }
}
