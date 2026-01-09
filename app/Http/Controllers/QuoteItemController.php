<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteItemController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_optional'] = $request->boolean('is_optional');

        $quote->items()->create($validated);
        $quote->refresh();
        $quote->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem eklendi.');
    }

    public function update(Request $request, Quote $quote, QuoteItem $item)
    {
        if ($item->quote_id !== $quote->id) {
            abort(404);
        }

        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_optional'] = $request->boolean('is_optional');

        $item->update($validated);
        $quote->refresh();
        $quote->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem güncellendi.');
    }

    public function destroy(Quote $quote, QuoteItem $item)
    {
        if ($item->quote_id !== $quote->id) {
            abort(404);
        }

        $item->delete();
        $quote->refresh();
        $quote->recalculateTotals();

        return redirect()->back()->with('success', 'Kalem silindi.');
    }

    private function rules(): array
    {
        $types = array_keys(config('quotes.item_types', []));

        return [
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
}
