<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_quote_with_items_and_currency_id(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create([
            'code' => 'EUR',
            'symbol' => '€',
            'is_active' => true,
        ]);
        $customer = Customer::factory()->create();
        $vessel = Vessel::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($user)->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'vessel_id' => $vessel->id,
            'work_order_id' => null,
            'title' => 'Yeni teklif',
            'status' => 'draft',
            'issued_at' => now()->toDateString(),
            'currency_id' => $currency->id,
            'validity_days' => 5,
            'estimated_duration_days' => 3,
            'payment_terms' => 'Ödeme teslimde yapılacaktır.',
            'warranty_text' => null,
            'exclusions' => null,
            'notes' => null,
            'fx_note' => null,
            'items' => [
                [
                    'title' => 'Bakım',
                    'description' => 'Motor bakım ve kontrol',
                    'amount' => '1250,50',
                    'vat_rate' => '18',
                ],
                [
                    'title' => 'Kontrol',
                    'description' => 'Saha kontrolü',
                    'amount' => '500',
                    'vat_rate' => null,
                ],
            ],
        ]);

        $response->assertRedirect(route('quotes.index'));

        $quote = Quote::latest('id')->first();

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'currency_id' => $currency->id,
        ]);
        $this->assertDatabaseCount('quote_items', 2);

        $quote->refresh();
        $this->assertEqualsWithDelta(1750.50, (float) $quote->subtotal, 0.01);
        $this->assertEqualsWithDelta(225.09, (float) $quote->vat_total, 0.01);
        $this->assertEqualsWithDelta(1975.59, (float) $quote->grand_total, 0.01);
    }

    public function test_user_can_update_quote_items_and_totals(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create(['is_active' => true]);
        $customer = Customer::factory()->create();
        $vessel = Vessel::factory()->create(['customer_id' => $customer->id]);
        $quote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'vessel_id' => $vessel->id,
            'currency_id' => $currency->id,
            'currency' => $currency->code,
            'issued_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $item = $quote->items()->create([
            'section' => 'Eski',
            'item_type' => 'other',
            'description' => 'Eski açıklama',
            'qty' => 1,
            'unit' => null,
            'unit_price' => 100,
            'discount_amount' => 0,
            'vat_rate' => 10,
            'is_optional' => false,
            'sort_order' => 0,
        ]);

        $quote->recalculateTotals();

        $response = $this->actingAs($user)->put(route('quotes.update', $quote), [
            'customer_id' => $quote->customer_id,
            'vessel_id' => $quote->vessel_id,
            'work_order_id' => $quote->work_order_id,
            'title' => $quote->title,
            'status' => $quote->status,
            'issued_at' => now()->toDateString(),
            'currency_id' => $currency->id,
            'validity_days' => $quote->validity_days,
            'estimated_duration_days' => $quote->estimated_duration_days,
            'payment_terms' => $quote->payment_terms,
            'warranty_text' => $quote->warranty_text,
            'exclusions' => $quote->exclusions,
            'notes' => $quote->notes,
            'fx_note' => $quote->fx_note,
            'items' => [
                [
                    'id' => $item->id,
                    'title' => 'Güncel',
                    'description' => 'Güncel açıklama',
                    'amount' => '200',
                    'vat_rate' => '10',
                ],
                [
                    'title' => 'Yeni',
                    'description' => 'Yeni açıklama',
                    'amount' => '300',
                    'vat_rate' => '0',
                ],
            ],
        ]);

        $response->assertRedirect(route('quotes.show', $quote));

        $quote->refresh();
        $this->assertDatabaseCount('quote_items', 2);
        $this->assertEqualsWithDelta(500, (float) $quote->subtotal, 0.01);
        $this->assertEqualsWithDelta(20, (float) $quote->vat_total, 0.01);
        $this->assertEqualsWithDelta(520, (float) $quote->grand_total, 0.01);
    }
}
