<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vessel;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $vessels = Vessel::query()
            ->with('customer')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('vessels.index', compact('vessels', 'search'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('vessels.create', [
            'customers' => $customers,
            'vessel' => new Vessel(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Vessel::create($validated);

        return redirect()->route('vessels.index')
            ->with('success', 'Tekne kaydı oluşturuldu.');
    }

    public function show(Vessel $vessel)
    {
        $vessel->load('customer');

        return view('vessels.show', compact('vessel'));
    }

    public function edit(Vessel $vessel)
    {
        $customers = Customer::orderBy('name')->get();

        return view('vessels.edit', compact('vessel', 'customers'));
    }

    public function update(Request $request, Vessel $vessel)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $vessel->update($validated);

        return redirect()->route('vessels.show', $vessel)
            ->with('success', 'Tekne kaydı güncellendi.');
    }

    public function destroy(Vessel $vessel)
    {
        $vessel->delete();

        return redirect()->route('vessels.index')
            ->with('success', 'Tekne kaydı silindi.');
    }

    private function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Müşteri seçimi zorunludur.',
            'customer_id.exists' => 'Seçilen müşteri geçersiz.',
            'name.required' => 'Tekne adı zorunludur.',
            'name.max' => 'Tekne adı en fazla 255 karakter olabilir.',
            'type.max' => 'Tekne tipi en fazla 255 karakter olabilir.',
            'registration_number.max' => 'Ruhsat numarası en fazla 255 karakter olabilir.',
        ];
    }
}
