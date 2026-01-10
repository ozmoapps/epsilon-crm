<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardrailLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_quote_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['created_by' => $user->id]);

        SalesOrder::factory()->create([
            'quote_id' => $quote->id,
            'customer_id' => $quote->customer_id,
            'vessel_id' => $quote->vessel_id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('quotes.update', $quote), $this->quotePayload($quote, [
            'title' => 'Kilidi aşma denemesi',
        ]));

        $response->assertRedirect(route('quotes.show', $quote));
        $response->assertSessionHas('error', 'Bu teklif siparişe dönüştürüldüğü için düzenlenemez.');
        $this->assertDatabaseMissing('quotes', [
            'id' => $quote->id,
            'title' => 'Kilidi aşma denemesi',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('quotes.destroy', $quote));

        $deleteResponse->assertRedirect(route('quotes.show', $quote));
        $deleteResponse->assertSessionHas('error', 'Bu teklifin bağlı siparişi olduğu için silinemez.');
        $this->assertDatabaseHas('quotes', ['id' => $quote->id]);
    }

    public function test_unlocked_quote_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->put(route('quotes.update', $quote), $this->quotePayload($quote, [
            'title' => 'Güncellenmiş teklif',
        ]));

        $response->assertRedirect(route('quotes.show', $quote));
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'title' => 'Güncellenmiş teklif',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('quotes.destroy', $quote));

        $deleteResponse->assertRedirect(route('quotes.index'));
        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    }

    public function test_locked_sales_order_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);
        $salesOrder->load('customer');

        Contract::create([
            'sales_order_id' => $salesOrder->id,
            'status' => 'draft',
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $salesOrder->customer?->name ?? 'Müşteri',
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $salesOrder->customer?->address,
            'customer_email' => $salesOrder->customer?->email,
            'customer_phone' => $salesOrder->customer?->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('sales-orders.update', $salesOrder), $this->salesOrderPayload($salesOrder, [
            'title' => 'Kilidi aşma denemesi',
        ]));

        $response->assertRedirect(route('sales-orders.show', $salesOrder));
        $response->assertSessionHas('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id,
            'title' => 'Kilidi aşma denemesi',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('sales-orders.destroy', $salesOrder));

        $deleteResponse->assertRedirect(route('sales-orders.show', $salesOrder));
        $deleteResponse->assertSessionHas('error', 'Bu siparişin bağlı sözleşmesi olduğu için silinemez.');
        $this->assertDatabaseHas('sales_orders', ['id' => $salesOrder->id]);
    }

    public function test_unlocked_sales_order_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->put(route('sales-orders.update', $salesOrder), $this->salesOrderPayload($salesOrder, [
            'title' => 'Güncellenmiş sipariş',
        ]));

        $response->assertRedirect(route('sales-orders.show', $salesOrder));
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'title' => 'Güncellenmiş sipariş',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('sales-orders.destroy', $salesOrder));

        $deleteResponse->assertRedirect(route('sales-orders.index'));
        $this->assertDatabaseMissing('sales_orders', ['id' => $salesOrder->id]);
    }

    public function test_signed_contract_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);
        $salesOrder->load('customer');

        $contract = Contract::create([
            'sales_order_id' => $salesOrder->id,
            'status' => 'signed',
            'issued_at' => now()->toDateString(),
            'signed_at' => now(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $salesOrder->customer?->name ?? 'Müşteri',
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $salesOrder->customer?->address,
            'customer_email' => $salesOrder->customer?->email,
            'customer_phone' => $salesOrder->customer?->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('contracts.update', $contract), [
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'contract_template_id' => null,
            'payment_terms' => $contract->payment_terms,
            'warranty_terms' => $contract->warranty_terms,
            'scope_text' => $contract->scope_text,
            'exclusions_text' => $contract->exclusions_text,
            'delivery_terms' => $contract->delivery_terms,
        ]);

        $response->assertRedirect(route('contracts.show', $contract));
        $response->assertSessionHas('warning', 'Sadece taslak sözleşmeler düzenlenebilir.');

        $deleteResponse = $this->actingAs($user)->delete(route('contracts.destroy', $contract));

        $deleteResponse->assertRedirect(route('contracts.show', $contract));
        $deleteResponse->assertSessionHas('error', 'İmzalı sözleşmeler silinemez.');
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    private function quotePayload(Quote $quote, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $quote->customer_id,
            'vessel_id' => $quote->vessel_id,
            'work_order_id' => $quote->work_order_id,
            'title' => $quote->title,
            'status' => $quote->status,
            'currency' => $quote->currency,
            'validity_days' => $quote->validity_days,
            'estimated_duration_days' => $quote->estimated_duration_days,
            'payment_terms' => $quote->payment_terms,
            'warranty_text' => $quote->warranty_text,
            'exclusions' => $quote->exclusions,
            'notes' => $quote->notes,
            'fx_note' => $quote->fx_note,
        ], $overrides);
    }

    private function salesOrderPayload(SalesOrder $salesOrder, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $salesOrder->customer_id,
            'vessel_id' => $salesOrder->vessel_id,
            'work_order_id' => $salesOrder->work_order_id,
            'title' => $salesOrder->title,
            'status' => $salesOrder->status,
            'currency' => $salesOrder->currency,
            'order_date' => $salesOrder->order_date?->toDateString(),
            'delivery_place' => $salesOrder->delivery_place,
            'delivery_days' => $salesOrder->delivery_days,
            'payment_terms' => $salesOrder->payment_terms,
            'warranty_text' => $salesOrder->warranty_text,
            'exclusions' => $salesOrder->exclusions,
            'notes' => $salesOrder->notes,
            'fx_note' => $salesOrder->fx_note,
        ], $overrides);
    }
}
