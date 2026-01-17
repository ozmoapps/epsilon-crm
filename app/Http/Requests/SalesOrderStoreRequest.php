<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SalesOrder;
use App\Models\Vessel;

class SalesOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_keys(SalesOrder::statusOptions());

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
            'work_order_id' => [
                'nullable',
                Rule::exists('work_orders', 'id')->where(function ($query) {
                    $tenantId = app(\App\Services\TenantContext::class)->id();
                    if ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }
                    return $query;
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'currency' => ['required', 'string', 'max:10'],
            'order_date' => ['nullable', 'date'],
            'delivery_place' => ['nullable', 'string', 'max:255'],
            'delivery_days' => ['nullable', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'warranty_text' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'fx_note' => ['nullable', 'string'],
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
            'title.required' => 'Sipariş başlığı zorunludur.',
            'title.max' => 'Sipariş başlığı en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.in' => 'Durum seçimi geçersiz.',
            'currency.required' => 'Para birimi zorunludur.',
            'currency.max' => 'Para birimi en fazla 10 karakter olabilir.',
            'order_date.date' => 'Sipariş tarihi geçerli değil.',
            'delivery_place.max' => 'Teslim yeri en fazla 255 karakter olabilir.',
            'delivery_days.integer' => 'Teslim günü sayısal olmalıdır.',
            'delivery_days.min' => 'Teslim günü negatif olamaz.',
        ];
    }
}
