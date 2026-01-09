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
            'boat_type' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('vessels.boat_types', [])))],
            'material' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('vessels.materials', [])))],
            'loa_m' => ['nullable', 'numeric', 'min:0'],
            'beam_m' => ['nullable', 'numeric', 'min:0'],
            'draft_m' => ['nullable', 'numeric', 'min:0'],
            'net_tonnage' => ['nullable', 'numeric', 'min:0'],
            'gross_tonnage' => ['nullable', 'numeric', 'min:0'],
            'passenger_capacity' => ['nullable', 'integer', 'min:0'],
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
            'type.max' => 'Marka en fazla 255 karakter olabilir.',
            'registration_number.max' => 'Model en fazla 255 karakter olabilir.',
            'boat_type.in' => 'Tekne sınıfı seçimi geçersiz.',
            'material.in' => 'Gövde malzemesi seçimi geçersiz.',
            'loa_m.numeric' => 'LOA metre değeri sayısal olmalıdır.',
            'loa_m.min' => 'LOA metre değeri sıfırdan küçük olamaz.',
            'beam_m.numeric' => 'Genişlik metre değeri sayısal olmalıdır.',
            'beam_m.min' => 'Genişlik metre değeri sıfırdan küçük olamaz.',
            'draft_m.numeric' => 'Draft metre değeri sayısal olmalıdır.',
            'draft_m.min' => 'Draft metre değeri sıfırdan küçük olamaz.',
            'net_tonnage.numeric' => 'Net tonaj değeri sayısal olmalıdır.',
            'net_tonnage.min' => 'Net tonaj değeri sıfırdan küçük olamaz.',
            'gross_tonnage.numeric' => 'Brüt tonaj değeri sayısal olmalıdır.',
            'gross_tonnage.min' => 'Brüt tonaj değeri sıfırdan küçük olamaz.',
            'passenger_capacity.integer' => 'Yolcu kapasitesi tam sayı olmalıdır.',
            'passenger_capacity.min' => 'Yolcu kapasitesi sıfırdan küçük olamaz.',
        ];
    }
}
