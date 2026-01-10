<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        // Prevent modifying yourself to remove admin rights if you are the logged in user?
        // Actually the requirement is: "Kendini admin’den düşüremez (son admin kalma riskini engelle)"
        // Logic: if update makes is_admin=false, check if other admins exist.

        if (!$validated['is_admin'] && $user->is_admin) {
             // If user is trying to remove admin rights
             if ($user->id === auth()->id()) {
                 // Option 1: Prevent removing self admin rights completely?
                 // Req: "is_admin toggle ... update is_admin=false istenirken: sistemde başka admin var mı kontrol et; yoksa deny."
                 // But also "Kendini silemez".
                 // Let's implement strict check: cannot demote self if you are the last admin. 
                 // Actually, usually you shouldn't be able to demote YOURSELF at all in some systems, but here let's follow specific instruction.
                 
                 // Check if there are other admins
                 $otherAdminsCount = User::where('is_admin', true)->where('id', '!=', $user->id)->count();
                 if ($otherAdminsCount === 0) {
                     return redirect()->back()->with('error', 'Sistemdeki son yönetici yetkilerini kaldıramazsınız.');
                 }
             }
        }

        $user->update([
            'is_admin' => $validated['is_admin'],
        ]);

        return redirect()->back()->with('success', 'Kullanıcı yetkisi güncellendi.');
    }

    /**
     * Update the user's password.
     */
    public function password(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Şifre güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Kendinizi silebilirsiniz.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Kullanıcı silindi.');
    }
}
