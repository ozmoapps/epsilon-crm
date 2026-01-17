<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class TenantMemberController extends Controller
{
    public function index()
    {
        $tenant = Tenant::findOrFail(session('current_tenant_id'));
        
        $members = $tenant->users()
            ->withPivot('role')
            ->orderBy('name')
            ->get();

        return view('manage.members.index', compact('members'));
    }

    public function destroy(User $user)
    {
        $tenantId = session('current_tenant_id');
        $tenant = Tenant::findOrFail($tenantId);

        // Check if user is actually a member of this tenant
        if (!$tenant->users()->where('users.id', $user->id)->exists()) {
             abort(404, 'Kullanıcı bu firmada bulunamadı.');
        }

        // Self-Removal Guard
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kendinizi firmadan çıkaramazsınız.');
        }

        $tenant->users()->detach($user->id);

        return back()->with('success', 'Üye firmadan çıkarıldı.');
    }
}
