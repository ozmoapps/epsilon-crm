<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'exists:products,id'],
            'description' => ['required_without:product_id', 'nullable', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Seçilen ürün geçerli değil.',
            'description.required_without' => 'Ürün seçilmediğinde açıklama zorunludur.',
            'qty.required' => 'Miktar zorunludur.',
            'qty.min' => 'Miktar 0\'dan büyük olmalıdır.',
            'unit.max' => 'Birim en fazla 50 karakter olabilir.',
        ];
    }
    
    protected function prepareForValidation()
    {
        // Normalize quantity if it comes as string with comma
        if ($this->has('qty') && is_string($this->input('qty'))) {
            $this->merge([
                'qty' => str_replace(',', '.', $this->input('qty'))
            ]);
        }
    }
}
