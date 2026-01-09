<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_contract_from_sales_order(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('sales-orders.contracts.store', $salesOrder), [
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'payment_terms' => 'Test ödeme şartları',
            'warranty_terms' => 'Test garanti',
            'scope_text' => 'Test kapsam',
            'exclusions_text' => 'Test hariçler',
            'delivery_terms' => 'Test teslim',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'sales_order_id' => $salesOrder->id,
        ]);
    }

    public function test_contract_numbers_are_sequential_per_year(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');

        $user = User::factory()->create();
        $salesOrderOne = SalesOrder::factory()->create(['created_by' => $user->id]);
        $salesOrderTwo = SalesOrder::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('sales-orders.contracts.store', $salesOrderOne), [
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
        ]);

        $this->actingAs($user)->post(route('sales-orders.contracts.store', $salesOrderTwo), [
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
        ]);

        $contractNumbers = Contract::orderBy('id')->pluck('contract_no')->all();

        $this->assertSame(['CT-2026-0001', 'CT-2026-0002'], $contractNumbers);

        Carbon::setTestNow();
    }

    public function test_only_draft_contracts_are_editable(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);
        $salesOrder->load('customer');

        $contract = Contract::create([
            'sales_order_id' => $salesOrder->id,
            'status' => 'sent',
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $salesOrder->customer->name,
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $salesOrder->customer->address,
            'customer_email' => $salesOrder->customer->email,
            'customer_phone' => $salesOrder->customer->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'payment_terms' => 'Eski ödeme',
            'warranty_terms' => 'Eski garanti',
            'scope_text' => 'Eski kapsam',
            'exclusions_text' => 'Eski hariçler',
            'delivery_terms' => 'Eski teslim',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('contracts.update', $contract), [
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'payment_terms' => 'Yeni ödeme',
            'warranty_terms' => 'Yeni garanti',
            'scope_text' => 'Yeni kapsam',
            'exclusions_text' => 'Yeni hariçler',
            'delivery_terms' => 'Yeni teslim',
        ]);

        $response->assertRedirect(route('contracts.show', $contract));
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'payment_terms' => 'Eski ödeme',
        ]);
    }

    public function test_show_contract_returns_success(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);

        $contract = $this->createContract($user, $salesOrder);

        $response = $this->actingAs($user)->get(route('contracts.show', $contract));

        $response->assertStatus(200);
    }

    public function test_pdf_route_returns_success(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);
        $salesOrder->load('customer');

        $contract = Contract::create([
            'sales_order_id' => $salesOrder->id,
            'status' => 'draft',
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $salesOrder->customer->name,
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $salesOrder->customer->address,
            'customer_email' => $salesOrder->customer->email,
            'customer_phone' => $salesOrder->customer->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'payment_terms' => 'Ödeme',
            'warranty_terms' => 'Garanti',
            'scope_text' => 'Kapsam',
            'exclusions_text' => 'Hariçler',
            'delivery_terms' => 'Teslim',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('contracts.pdf', $contract));

        $response->assertStatus(200);
    }

    public function test_revision_creation_creates_new_draft_and_updates_current(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);

        $rootContract = $this->createContract($user, $salesOrder, [
            'status' => 'sent',
        ]);

        $response = $this->actingAs($user)->post(route('contracts.revise', $rootContract));

        $response->assertRedirect();

        $revision = Contract::query()
            ->where('root_contract_id', $rootContract->id)
            ->where('revision_no', 2)
            ->first();

        $this->assertNotNull($revision);
        $this->assertSame($rootContract->id, $revision->root_contract_id);
        $this->assertSame('draft', $revision->status);
        $this->assertSame($rootContract->contract_no . '-R2', $revision->contract_no);

        $this->assertDatabaseHas('contracts', [
            'id' => $rootContract->id,
            'is_current' => false,
            'superseded_by_id' => $revision->id,
        ]);
        $this->assertDatabaseHas('contracts', [
            'id' => $revision->id,
            'is_current' => true,
        ]);

        $currentCount = Contract::query()
            ->where(function ($query) use ($rootContract) {
                $query->where('id', $rootContract->id)
                    ->orWhere('root_contract_id', $rootContract->id);
            })
            ->where('is_current', true)
            ->count();

        $this->assertSame(1, $currentCount);
    }

    public function test_pdf_route_returns_success_for_revision(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $user->id]);

        $rootContract = $this->createContract($user, $salesOrder, [
            'status' => 'sent',
        ]);

        $revision = $this->createContract($user, $salesOrder, [
            'root_contract_id' => $rootContract->id,
            'revision_no' => 2,
            'contract_no' => $rootContract->contract_no . '-R2',
            'status' => 'draft',
            'rendered_body' => '<p>Revizyon</p>',
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)->get(route('contracts.pdf', $revision));

        $response->assertStatus(200);
    }

    private function createContract(User $user, SalesOrder $salesOrder, array $overrides = []): Contract
    {
        $salesOrder->loadMissing('customer');

        return Contract::create(array_merge([
            'sales_order_id' => $salesOrder->id,
            'status' => 'draft',
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $salesOrder->customer->name,
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $salesOrder->customer->address,
            'customer_email' => $salesOrder->customer->email,
            'customer_phone' => $salesOrder->customer->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'payment_terms' => 'Ödeme',
            'warranty_terms' => 'Garanti',
            'scope_text' => 'Kapsam',
            'exclusions_text' => 'Hariçler',
            'delivery_terms' => 'Teslim',
            'created_by' => $user->id,
        ], $overrides));
    }
}
