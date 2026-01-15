<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesOrderItemController extends Controller
{
    public function store(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->isLocked()) {
            return redirect()->back()
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        $this->normalizeNumericInputs($request);
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_optional'] = $request->boolean('is_optional');

        $salesOrder->items()->create($validated);
        $salesOrder->refresh();
        $salesOrder->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem eklendi.');
    }

    public function update(Request $request, SalesOrder $salesOrder, SalesOrderItem $item)
    {
        if ($item->sales_order_id !== $salesOrder->id) {
            abort(404);
        }

        if ($salesOrder->isLocked()) {
            return redirect()->back()
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        $this->normalizeNumericInputs($request);
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_optional'] = $request->boolean('is_optional');

        $item->update($validated);
        $salesOrder->refresh();
        $salesOrder->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem güncellendi.');
    }

    public function destroy(SalesOrder $salesOrder, SalesOrderItem $item)
    {
        if ($item->sales_order_id !== $salesOrder->id) {
            abort(404);
        }

        if ($salesOrder->isLocked()) {
            return redirect()->back()
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        $item->delete();
        $salesOrder->refresh();
        $salesOrder->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem silindi.');
    }

    private function rules(): array
    {
        $types = array_keys(config('sales_orders.item_types', []));

        return [
            'sales_order_id' => ['required', 'exists:sales_orders,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'section' => ['nullable', 'string', 'max:255'],
            'item_type' => ['required', 'string', Rule::in($types)],
            'description' => ['required', 'string'],
            'qty' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_optional' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'section.max' => 'Bölüm adı en fazla 255 karakter olabilir.',
            'item_type.required' => 'Kalem tipi zorunludur.',
            'item_type.in' => 'Kalem tipi geçersiz.',
            'description.required' => 'Açıklama zorunludur.',
            'qty.required' => 'Miktar zorunludur.',
            'qty.numeric' => 'Miktar sayısal olmalıdır.',
            'qty.min' => 'Miktar negatif olamaz.',
            'unit.max' => 'Birim alanı en fazla 50 karakter olabilir.',
            'unit_price.required' => 'Birim fiyat zorunludur.',
            'unit_price.numeric' => 'Birim fiyat sayısal olmalıdır.',
            'unit_price.min' => 'Birim fiyat negatif olamaz.',
            'discount_amount.numeric' => 'İndirim sayısal olmalıdır.',
            'discount_amount.min' => 'İndirim negatif olamaz.',
            'vat_rate.numeric' => 'KDV oranı sayısal olmalıdır.',
            'vat_rate.min' => 'KDV oranı negatif olamaz.',
            'vat_rate.max' => 'KDV oranı en fazla 100 olabilir.',
            'sort_order.integer' => 'Sıra bilgisi sayısal olmalıdır.',
            'sort_order.min' => 'Sıra bilgisi negatif olamaz.',
        ];
    }

    private function normalizeNumericInputs(Request $request): void
    {
        $fields = ['qty', 'unit_price', 'discount_amount', 'vat_rate'];
        $normalized = [];

        foreach ($fields as $field) {
            $value = $request->input($field);

            if (is_string($value)) {
                $normalized[$field] = str_replace(',', '.', $value);
            }
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }
}
