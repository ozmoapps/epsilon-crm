<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Product;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_convert_quote_to_sales_order_with_items_and_products()
    {
        $user = User::factory()->create();
        $vessel = Vessel::factory()->create();
        $product = Product::factory()->create();

        $quote = Quote::factory()->create([
            'vessel_id' => $vessel->id,
            'customer_id' => $vessel->customer_id,
            'status' => 'accepted',
        ]);

        $item1 = $quote->items()->create([
            'item_type' => 'material',
            'product_id' => $product->id, // Linked Item
            'description' => 'Linked Product Item',
            'qty' => 5,
            'unit_price' => 100,
        ]);

        $item2 = $quote->items()->create([
            'item_type' => 'labor',
            'product_id' => null, // Free text Item
            'description' => 'Labor Service',
            'qty' => 10,
            'unit_price' => 50,
        ]);

        $response = $this->actingAs($user)->post(route('quotes.convert_to_sales_order', $quote));

        $response->assertRedirect();
        
        $quote->refresh();
        $this->assertNotNull($quote->sales_order_id);
        $this->assertNotNull($quote->converted_at);

        $salesOrder = SalesOrder::find($quote->sales_order_id);
        $this->assertEquals($quote->customer_id, $salesOrder->customer_id);
        $this->assertEquals($quote->vessel_id, $salesOrder->vessel_id);

        // Check Items
        $this->assertCount(2, $salesOrder->items);
        
        $soItem1 = $salesOrder->items->where('description', 'Linked Product Item')->first();
        $this->assertEquals($product->id, $soItem1->product_id); // Critical Assertion
        $this->assertEquals(5, $soItem1->qty);

        $soItem2 = $salesOrder->items->where('description', 'Labor Service')->first();
        $this->assertNull($soItem2->product_id);
    }

    public function test_conversion_is_idempotent()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['status' => 'accepted', 'vessel_id' => Vessel::factory()->create()->id]);

        // First Conversion
        $this->actingAs($user)->post(route('quotes.convert_to_sales_order', $quote));
        $quote->refresh();
        $firstSoId = $quote->sales_order_id;

        // Second Conversion
        $this->actingAs($user)->post(route('quotes.convert_to_sales_order', $quote));
        $quote->refresh();
        
        // Assert ID hasn't changed
        $this->assertEquals($firstSoId, $quote->sales_order_id);
        
        // Assert count is still 1
        $this->assertEquals(1, SalesOrder::where('quote_id', $quote->id)->count());
    }


}
