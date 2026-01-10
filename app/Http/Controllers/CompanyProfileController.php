<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CompanyProfile::class, 'companyProfile');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $companyProfiles = CompanyProfile::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('company_profiles.index', compact('companyProfiles', 'search'));
    }

    public function create()
    {
        $existing = CompanyProfile::query()->first();

        if ($existing) {
            return redirect()
                ->route('company-profiles.edit', $existing)
                ->with('info', 'Şirket profili zaten mevcut.');
        }

        return view('company_profiles.create', [
            'companyProfile' => new CompanyProfile(),
        ]);
    }

    public function store(Request $request)
    {
        $existing = CompanyProfile::query()->first();

        if ($existing) {
            return redirect()
                ->route('company-profiles.edit', $existing)
                ->with('info', 'Şirket profili zaten mevcut.');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $companyProfile = CompanyProfile::create($validated);

        return redirect()->route('company-profiles.show', $companyProfile)
            ->with('success', 'Şirket profili oluşturuldu.');
    }

    public function show(CompanyProfile $companyProfile)
    {
        return view('company_profiles.show', compact('companyProfile'));
    }

    public function edit(CompanyProfile $companyProfile)
    {
        return view('company_profiles.edit', compact('companyProfile'));
    }

    public function update(Request $request, CompanyProfile $companyProfile)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $companyProfile->update($validated);

        return redirect()->route('company-profiles.show', $companyProfile)
            ->with('success', 'Şirket profili güncellendi.');
    }

    public function destroy(CompanyProfile $companyProfile)
    {
        $companyProfile->delete();

        return redirect()->route('company-profiles.index')
            ->with('success', 'Şirket profili silindi.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_no' => ['nullable', 'string', 'max:50'],
            'footer_text' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Şirket adı zorunludur.',
            'name.max' => 'Şirket adı en fazla 255 karakter olabilir.',
            'address.max' => 'Adres en fazla 255 karakter olabilir.',
            'phone.max' => 'Telefon en fazla 50 karakter olabilir.',
            'email.email' => 'E-posta formatı geçerli değil.',
            'email.max' => 'E-posta en fazla 255 karakter olabilir.',
            'tax_no.max' => 'Vergi numarası en fazla 50 karakter olabilir.',
        ];
    }
}
