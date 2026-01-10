<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vessel;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_workspace_access()
    {
        // 1. Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // 2. User 1 creates a Customer (Foundation for others)
        $this->actingAs($user1);
        $customer = Customer::factory()->create(['created_by' => $user1->id]);
        
        // 3. User 2 should see this Customer in index
        $this->actingAs($user2);
        $response = $this->get(route('customers.index'));
        // $response->assertStatus(200); // Sometimes redirects to login if auth fail, or 500 if error.
        $response->assertOk();
        $response->assertSee($customer->name);

        // 4. User 2 should be able to update User 1's Customer
        $response = $this->put(route('customers.update', $customer), [
            'name' => 'Updated by User 2',
            'created_by' => $user1->id, 
            'phone' => '123456', // required fields might exist
        ]);
        // Update might redirect.
        $response->assertRedirect(); 
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated by User 2']);

        // 5. User 1 creates a Vessel
        $this->actingAs($user1);
        $vessel = Vessel::factory()->create(['customer_id' => $customer->id, 'created_by' => $user1->id]);

        // 6. User 2 sees Vessel
        $this->actingAs($user2);
        $response = $this->get(route('vessels.index'));
        $response->assertOk();
        $response->assertSee($vessel->name);

        // 7. User 1 creates a Quote
        $this->actingAs($user1);
        $quote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'vessel_id' => $vessel->id,
            'created_by' => $user1->id,
            'status' => 'draft',
            'title' => 'Test Quote',
            'currency_id' => \App\Models\Currency::factory(), // Ensure currency exists
            'issued_at' => now(),
        ]);

        // 8. User 2 sees Quote in Index
        $this->actingAs($user2);
        $response = $this->get(route('quotes.index'));
        $response->assertOk();
        $response->assertSee($quote->quote_no); // Assuming quote_no is visible

        // 9. User 2 can view Quote detail
        $response = $this->get(route('quotes.show', $quote));
        $response->assertOk();

        // 10. User 2 can update Quote (e.g. mark as sent is a status transition)
        // Let's try explicit update if route exists, or markAsSent
        $response = $this->post(route('quotes.mark_sent', $quote));
        $response->assertRedirect();
        $this->assertEquals('sent', $quote->fresh()->status);
    }
}
