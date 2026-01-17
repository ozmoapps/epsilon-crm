<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Quote;
use App\Models\Vessel;

class QuoteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('items')) {
            return;
        }

        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (!is_array($item)) return $item;

                $amount = \App\Support\MoneyMath::normalizeDecimalString($item['amount'] ?? null);
                $vatRate = \App\Support\MoneyMath::normalizeDecimalString($item['vat_rate'] ?? null, 2, true);

                return array_merge($item, [
                    'id' => $item['id'] ?? null,
                    'title' => isset($item['title']) ? trim((string) $item['title']) : null,
                    'description' => isset($item['description']) ? trim((string) $item['description']) : null,
                    'amount' => $amount,
                    'vat_rate' => $vatRate,
                ]);
            })
            ->filter(function (array $item) {
                return filled($item['title'])
                    || filled($item['description'])
                    || filled($item['amount'])
                    || filled($item['vat_rate']);
            })
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        $statuses = array_keys(Quote::statusOptions());

        return [
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where(function ($query) {
                    $tenantId = app(\App\Services\TenantContext::class)->id();
                    if ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }
                    return $query;
                }),
            ],
            'vessel_id' => [
                'required',
                Rule::exists('vessels', 'id')->where(function ($query) {
                    $tenantId = app(\App\Services\TenantContext::class)->id();
                    if ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }
                    return $query;
                }),
            ],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'issued_at' => ['required', 'date'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('is_active', true)],
            'validity_days' => ['nullable', 'integer', 'min:0'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'warranty_text' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'fx_note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => [
                'nullable', 
                'integer', 
                Rule::exists('quote_items', 'id')->where(function ($query) {
                    $query->where('quote_id', $this->route('quote')->id);
                    // Standard Quote/Item tenancy is handled by relation or quote-scoped access,
                    // but we can ensure items belong to current tenant too? 
                    // Usually items don't have tenant_id unless propagated. 
                    // Let's rely on quote_id match since quote access is Guarded by TenantGuard.
                    // But if items table has tenant_id, we should add it.
                    // Assuming items table has tenant_id as per Phase 2B.
                    if (app(\App\Services\TenantContext::class)->id()) {
                         $query->where('tenant_id', app(\App\Services\TenantContext::class)->id());
                    }
                })
            ],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerId = $this->input('customer_id');
            $vesselId = $this->input('vessel_id');

            if ($customerId && $vesselId) {
                $exists = Vessel::where('id', $vesselId)
                    ->where('customer_id', $customerId)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('vessel_id', 'Seçilen tekne seçilen müşteriye ait değil.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Müşteri seçimi zorunludur.',
            'customer_id.exists' => 'Seçilen müşteri geçersiz.',
            'vessel_id.required' => 'Tekne seçimi zorunludur.',
            'vessel_id.exists' => 'Seçilen tekne geçersiz.',
            'work_order_id.exists' => 'Seçilen iş emri geçersiz.',
            'title.required' => 'Teklif konusu zorunludur.',
            'title.max' => 'Teklif konusu en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.in' => 'Durum seçimi geçersiz.',
            'issued_at.required' => 'Teklif tarihi zorunludur.',
            'issued_at.date' => 'Teklif tarihi geçerli değil.',
            'contact_name.max' => 'İletişim kişisi en fazla 255 karakter olabilir.',
            'contact_phone.max' => 'İletişim telefonu en fazla 255 karakter olabilir.',
            'location.max' => 'Lokasyon en fazla 255 karakter olabilir.',
            'currency_id.required' => 'Para birimi zorunludur.',
            'currency_id.exists' => 'Seçilen para birimi geçersiz.',
            'validity_days.integer' => 'Geçerlilik günü sayısal olmalıdır.',
            'validity_days.min' => 'Geçerlilik günü negatif olamaz.',
            'estimated_duration_days.integer' => 'Tahmini süre sayısal olmalıdır.',
            'estimated_duration_days.min' => 'Tahmini süre negatif olamaz.',
            'items.array' => 'Kalem listesi geçerli değil.',
            'items.*.title.required' => 'Kalem başlığı zorunludur.',
            'items.*.title.max' => 'Kalem başlığı en fazla 255 karakter olabilir.',
            'items.*.description.required' => 'Kalem açıklaması zorunludur.',
            'items.*.amount.required' => 'Kalem tutarı zorunludur.',
            'items.*.amount.numeric' => 'Kalem tutarı sayısal olmalıdır.',
            'items.*.amount.min' => 'Kalem tutarı negatif olamaz.',
            'items.*.vat_rate.numeric' => 'KDV oranı sayısal olmalıdır.',
            'items.*.vat_rate.min' => 'KDV oranı negatif olamaz.',
        ];
    }
}
