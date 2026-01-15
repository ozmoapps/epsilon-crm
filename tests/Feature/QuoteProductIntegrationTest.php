<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteProductIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_product_id_to_quote_item()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'sku' => 'TP-001']);

        $response = $this->actingAs($user)->post(route('quotes.items.store', $quote), [
            'item_type' => 'material',
            'product_id' => $product->id,
            'description' => 'Custom Description',
            'qty' => 1,
            'unit_price' => 100,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'description' => 'Custom Description'
        ]);
    }

    public function test_can_update_quote_item_with_product_id()
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $product = Product::factory()->create();
        $item = $quote->items()->create([
            'item_type' => 'material',
            'description' => 'Initial',
            'qty' => 1,
            'unit_price' => 10
        ]);

        $response = $this->actingAs($user)->put(route('quotes.items.update', [$quote, $item]), [
            'item_type' => 'material',
            'product_id' => $product->id,
            'description' => 'Updated',
            'qty' => 1,
            'unit_price' => 10
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('quote_items', [
            'id' => $item->id,
            'product_id' => $product->id,
            'description' => 'Updated'
        ]);
    }
}
