<?php

namespace App\Http\Controllers;

use App\Models\Vessel;
use App\Models\VesselContact;
use Illuminate\Http\Request;

class VesselContactController extends Controller
{
    public function store(Request $request, Vessel $vessel)
    {
        $this->authorize('update', $vessel); // Assuming edit permission on vessel allows adding contacts

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'role.required' => 'Rol alanı zorunludur.',
            'name.required' => 'İsim alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
        ]);

        $vessel->contacts()->create($validated);

        return back()->with('success', 'İletişim kişisi eklendi.');
    }

    public function destroy(Vessel $vessel, VesselContact $contact)
    {
        $this->authorize('update', $vessel);
        
        // Ensure contact belongs to vessel
        if ($contact->vessel_id !== $vessel->id) {
            abort(404);
        }

        $contact->delete();

        return back()->with('success', 'İletişim kişisi silindi.');
    }
}
