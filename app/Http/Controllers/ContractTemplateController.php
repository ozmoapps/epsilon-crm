<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use App\Services\ContractTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

        $template = null;

        DB::transaction(function () use ($validated, $request, &$template) {
            $template = ContractTemplate::create(array_merge(
                Arr::except($validated, ['change_note']),
                ['created_by' => $request->user()->id]
            ));

            $template->createVersion(
                $validated['content'],
                $validated['format'],
                $request->user()->id,
                $validated['change_note'] ?? null
            );
        });

        $this->syncDefault($template, $validated['is_default'] ?? false);

        return redirect()->route('admin.contract-templates.edit', $template)
            ->with('success', 'Sözleşme şablonu oluşturuldu.');
    }

    public function edit(ContractTemplate $template, ContractTemplateRenderer $renderer)
    {
        return view('contract_templates.edit', [
            'template' => $template,
            'locales' => config('contracts.locales', []),
            'formats' => $this->formats(),
            'previewHtml' => $this->buildPreview($renderer, $template),
            'versions' => $template->versions()->with('creator')->orderByDesc('version')->get(),
        ]);
    }

    public function update(Request $request, ContractTemplate $template)
    {
        $validated = $this->normalizeBooleans(
            $request->validate($this->rules(), $this->messages()),
            $request
        );

        DB::transaction(function () use ($validated, $request, $template) {
            $template->update(Arr::except($validated, ['change_note']));

            $template->createVersion(
                $validated['content'],
                $validated['format'],
                $request->user()->id,
                $validated['change_note'] ?? null
            );
        });

        $this->syncDefault($template, $validated['is_default'] ?? false);

        return redirect()->route('admin.contract-templates.edit', $template)
            ->with('success', 'Sözleşme şablonu güncellendi.');
    }

    public function show(Request $request, ContractTemplate $template, ContractTemplateRenderer $renderer)
    {
        $template->load(['currentVersion', 'versions.creator']);

        $selectedVersion = $template->currentVersion;
        $versionId = $request->input('version');

        if ($versionId) {
            $selectedVersion = $template->versions()->whereKey($versionId)->firstOrFail();
        }

        return view('contract_templates.show', [
            'template' => $template,
            'versions' => $template->versions->sortByDesc('version'),
            'selectedVersion' => $selectedVersion,
            'previewHtml' => $selectedVersion ? $this->buildPreview($renderer, $selectedVersion) : null,
        ]);
    }

    public function restore(Request $request, ContractTemplate $template, ContractTemplateVersion $version)
    {
        $this->authorize('update', $template);

        if ($version->contract_template_id !== $template->id) {
            abort(404);
        }

        $template->createVersion(
            $version->content,
            $version->format,
            $request->user()->id,
            "Sürüm {$version->version} geri yüklendi."
        );

        return redirect()->route('admin.contract-templates.show', $template)
            ->with('success', 'Şablon önceki sürüme geri yüklendi.');
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
        $template = new ContractTemplate(Arr::except($validated, ['change_note']));

        if ($templateId) {
            $existing = ContractTemplate::query()->findOrFail($templateId);
            $this->authorize('update', $existing);
            $existing->fill(Arr::except($validated, ['change_note']));

            return view('contract_templates.edit', [
                'template' => $existing,
                'locales' => config('contracts.locales', []),
                'formats' => $this->formats(),
                'previewHtml' => $this->buildPreview($renderer, $template),
                'versions' => $existing->versions()->with('creator')->orderByDesc('version')->get(),
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

    private function buildPreview(ContractTemplateRenderer $renderer, ContractTemplate|ContractTemplateVersion $template): ?string
    {
        $contract = Contract::query()
            ->with(['salesOrder.items', 'salesOrder.customer'])
            ->latest('id')
            ->first();

        $locale = $template instanceof ContractTemplate
            ? ($template->locale ?: 'tr')
            : ($template->template?->locale ?: 'tr');

        if (! $contract) {
            $contract = new Contract([
                'contract_no' => 'CT-2026-0001',
                'issued_at' => now(),
                'locale' => $locale,
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
            'change_note' => ['nullable', 'string', 'max:255'],
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
