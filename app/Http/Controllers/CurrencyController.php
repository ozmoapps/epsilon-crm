<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Currency::class, 'currency');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $currencies = Currency::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('currencies.index', compact('currencies', 'search'));
    }

    public function create()
    {
        return view('currencies.create', [
            'currency' => new Currency(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['code'] = mb_strtoupper($validated['code']);

        $currency = Currency::create($validated);

        return redirect()->route('admin.currencies.show', $currency)
            ->with('success', 'Para birimi oluşturuldu.');
    }

    public function show(Currency $currency)
    {
        return view('currencies.show', compact('currency'));
    }

    public function edit(Currency $currency)
    {
        return view('currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate($this->rules($currency), $this->messages());
        $validated['code'] = mb_strtoupper($validated['code']);

        $currency->update($validated);

        return redirect()->route('admin.currencies.show', $currency)
            ->with('success', 'Para birimi güncellendi.');
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return redirect()->route('admin.currencies.index')
            ->with('success', 'Para birimi silindi.');
    }

    private function rules(?Currency $currency = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'code')->ignore($currency?->id),
            ],
            'symbol' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Para birimi adı zorunludur.',
            'name.max' => 'Para birimi adı en fazla 255 karakter olabilir.',
            'code.required' => 'Kod zorunludur.',
            'code.max' => 'Kod en fazla 10 karakter olabilir.',
            'code.unique' => 'Bu kod zaten kullanılıyor.',
            'symbol.max' => 'Sembol en fazla 10 karakter olabilir.',
        ];
    }
}
