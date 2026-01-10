<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_store_persists_currency_id(): void
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
            'title' => 'Test teklifi',
            'status' => 'draft',
            'issued_at' => now()->toDateString(),
            'currency_id' => $currency->id,
            'validity_days' => 10,
            'estimated_duration_days' => 5,
            'payment_terms' => 'Peşin ödeme',
            'warranty_text' => null,
            'exclusions' => null,
            'notes' => null,
            'fx_note' => null,
        ]);

        $response->assertRedirect(route('quotes.index'));
        $this->assertDatabaseHas('quotes', [
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'currency' => 'EUR',
        ]);
    }

    public function test_quote_pdf_endpoint_returns_pdf(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $quote = Quote::factory()->create([
            'created_by' => $user->id,
            'currency_id' => $currency->id,
            'currency' => $currency->code,
        ]);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_quote_pdf_endpoint_is_forbidden_for_other_users(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $currency = Currency::factory()->create();
        $quote = Quote::factory()->create([
            'created_by' => $owner->id,
            'currency_id' => $currency->id,
            'currency' => $currency->code,
        ]);

        $response = $this->actingAs($otherUser)->get(route('quotes.pdf', $quote));

        $response->assertForbidden();
    }
}
