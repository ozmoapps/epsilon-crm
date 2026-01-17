<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Vessel;

class WorkOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('customers', 'id')->where(function ($query) {
                    $tenantId = app(\App\Services\TenantContext::class)->id();
                    if ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }
                    return $query;
                }),
            ],
            'vessel_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('vessels', 'id')->where(function ($query) {
                    $tenantId = app(\App\Services\TenantContext::class)->id();
                    if ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }
                    return $query;
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
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
            'title.required' => 'Başlık alanı zorunludur.',
            'title.max' => 'Başlık alanı en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.max' => 'Durum alanı en fazla 50 karakter olabilir.',
            'planned_start_at.date' => 'Planlanan başlangıç tarihi geçerli değil.',
            'planned_end_at.date' => 'Planlanan bitiş tarihi geçerli değil.',
            'planned_end_at.after_or_equal' => 'Planlanan bitiş tarihi başlangıç tarihinden önce olamaz.',
        ];
    }
}
