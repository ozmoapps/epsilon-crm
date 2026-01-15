<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['product', 'service'])],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products')->ignore($this->product)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products')->ignore($this->product)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'track_stock' => ['boolean'],
            'critical_stock_level' => ['nullable', 'integer', 'min:0'],
            'default_buy_price' => ['nullable', 'numeric', 'min:0'],
            'default_sell_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ];
    }
    
    protected function prepareForValidation()
    {
        $this->merge([
            'track_stock' => $this->boolean('track_stock'),
        ]);
        
        // Remove comma from numeric fields
        $numerics = ['default_buy_price', 'default_sell_price'];
        foreach ($numerics as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([$field => str_replace(',', '.', $this->input($field))]);
            }
        }
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Ürün/Hizmet adı zorunludur.',
            'sku.unique' => 'Bu SKU kodu başka bir üründe kullanılıyor.',
            'barcode.unique' => 'Bu barkod başka bir üründe kullanılıyor.',
        ];
    }
}
