<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_quote_status_transition_is_rejected(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create([
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('quotes.mark_accepted', $quote));

        $response->assertRedirect(route('quotes.show', $quote));
        $response->assertSessionHas('warning', 'Bu işlem için uygun durumda değil.');
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'draft',
        ]);

        $this->assertFalse(ActivityLog::query()
            ->where('subject_type', Quote::class)
            ->where('subject_id', $quote->id)
            ->where('action', 'status_changed')
            ->exists());
    }

    public function test_valid_quote_status_transition_logs_activity(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create([
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('quotes.mark_sent', $quote));

        $response->assertRedirect(route('quotes.show', $quote));
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'sent',
        ]);

        $log = ActivityLog::query()
            ->where('subject_type', Quote::class)
            ->where('subject_id', $quote->id)
            ->where('action', 'status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('draft', $log->meta['from'] ?? null);
        $this->assertSame('sent', $log->meta['to'] ?? null);
    }

    public function test_sales_order_status_transition_logs_activity(): void
    {
        $user = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->patch(route('sales-orders.confirm', $salesOrder));

        $response->assertRedirect();
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'status' => 'confirmed',
        ]);

        $log = ActivityLog::query()
            ->where('subject_type', SalesOrder::class)
            ->where('subject_id', $salesOrder->id)
            ->where('action', 'status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('draft', $log->meta['from'] ?? null);
        $this->assertSame('confirmed', $log->meta['to'] ?? null);
    }
}
