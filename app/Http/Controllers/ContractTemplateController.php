<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Services\ContractTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ContractTemplate::class, 'template');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $templates = ContractTemplate::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('contract_templates.index', compact('templates', 'search'));
    }

    public function create()
    {
        return view('contract_templates.create', [
            'template' => new ContractTemplate(),
            'locales' => config('contracts.locales', []),
            'formats' => $this->formats(),
            'previewHtml' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->normalizeBooleans(
            $request->validate($this->rules(), $this->messages()),
            $request
        );

        $template = ContractTemplate::create(array_merge($validated, [
            'created_by' => $request->user()->id,
        ]));

        $this->syncDefault($template, $validated['is_default'] ?? false);

        return redirect()->route('contract-templates.edit', $template)
            ->with('success', 'Sözleşme şablonu oluşturuldu.');
    }

    public function edit(ContractTemplate $template, ContractTemplateRenderer $renderer)
    {
        return view('contract_templates.edit', [
            'template' => $template,
            'locales' => config('contracts.locales', []),
            'formats' => $this->formats(),
            'previewHtml' => $this->buildPreview($renderer, $template),
        ]);
    }

    public function update(Request $request, ContractTemplate $template)
    {
        $validated = $this->normalizeBooleans(
            $request->validate($this->rules(), $this->messages()),
            $request
        );

        $template->update($validated);
        $this->syncDefault($template, $validated['is_default'] ?? false);

        return redirect()->route('contract-templates.edit', $template)
            ->with('success', 'Sözleşme şablonu güncellendi.');
    }

    public function makeDefault(ContractTemplate $template)
    {
        $this->syncDefault($template, true);

        return back()->with('success', 'Varsayılan şablon güncellendi.');
    }

    public function toggleActive(ContractTemplate $template)
    {
        $template->update([
            'is_active' => ! $template->is_active,
        ]);

        return back()->with('success', 'Şablon durumu güncellendi.');
    }

    public function preview(Request $request, ContractTemplateRenderer $renderer)
    {
        $validated = $this->normalizeBooleans(
            $request->validate($this->rules(), $this->messages()),
            $request
        );

        $templateId = $request->input('template_id');
        $template = new ContractTemplate($validated);

        if ($templateId) {
            $existing = ContractTemplate::query()->findOrFail($templateId);
            $this->authorize('update', $existing);
            $existing->fill($validated);

            return view('contract_templates.edit', [
                'template' => $existing,
                'locales' => config('contracts.locales', []),
                'formats' => $this->formats(),
                'previewHtml' => $this->buildPreview($renderer, $template),
            ]);
        }

        $this->authorize('create', ContractTemplate::class);

        return view('contract_templates.create', [
            'template' => $template,
            'locales' => config('contracts.locales', []),
            'formats' => $this->formats(),
            'previewHtml' => $this->buildPreview($renderer, $template),
        ]);
    }

    private function buildPreview(ContractTemplateRenderer $renderer, ContractTemplate $template): ?string
    {
        $contract = Contract::query()
            ->with(['salesOrder.items', 'salesOrder.customer'])
            ->latest('id')
            ->first();

        if (! $contract) {
            $contract = new Contract([
                'contract_no' => 'CT-2026-0001',
                'issued_at' => now(),
                'locale' => $template->locale ?: 'tr',
                'currency' => 'EUR',
                'customer_name' => 'Örnek Müşteri',
                'customer_company' => 'Örnek Şirket',
                'customer_tax_no' => '1234567890',
                'customer_address' => 'Örnek Mah. Örnek Sk. No: 1',
                'customer_email' => 'ornek@example.com',
                'customer_phone' => '+90 555 555 55 55',
                'subtotal' => 1000,
                'tax_total' => 180,
                'grand_total' => 1180,
            ]);
        }

        return $renderer->render($contract, $template);
    }

    private function formats(): array
    {
        return [
            'html' => 'HTML',
            'markdown' => 'Markdown',
            'text' => 'Metin',
        ];
    }

    private function rules(): array
    {
        $locales = array_keys(config('contracts.locales', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', Rule::in($locales)],
            'format' => ['required', 'string', Rule::in(array_keys($this->formats()))],
            'content' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Şablon adı zorunludur.',
            'locale.required' => 'Dil seçimi zorunludur.',
            'locale.in' => 'Dil seçimi geçersiz.',
            'format.required' => 'Format seçimi zorunludur.',
            'format.in' => 'Format seçimi geçersiz.',
            'content.required' => 'Şablon içeriği zorunludur.',
        ];
    }

    private function syncDefault(ContractTemplate $template, bool $isDefault): void
    {
        if (! $isDefault) {
            $template->update(['is_default' => false]);
            return;
        }

        ContractTemplate::query()
            ->where('locale', $template->locale)
            ->where('id', '!=', $template->id)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);
    }

    private function normalizeBooleans(array $validated, Request $request): array
    {
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
