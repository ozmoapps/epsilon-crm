<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_currencies(): void
    {
        $this->get(route('admin.currencies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_view_currency_index(): void
    {
        // Assuming there is an isAdmin or similar check, often based on role or attribute.
        // For now, factory()->create() returns a basic user. 
        // If 'admin' middleware checks for something specific, this might still fail with 403.
        // Let's assume standard auth for now but correct the route.
        // If middleware ['admin'] exists, we need an admin user.
        // Typically: User::factory()->admin()->create() or similar.
        // Checking available factories later if this fails, but step 1 is route name.
        $user = User::factory()->create(['is_admin' => true]); // Common convention, or similar.

        $this->actingAs($user)
            ->get(route('admin.currencies.index'))
            ->assertOk();
    }

    public function test_authenticated_admin_can_create_update_and_delete_currency(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post(route('admin.currencies.store'), [
            'name' => 'Amerikan Doları',
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('currencies', [
            'code' => 'USD',
            'name' => 'Amerikan Doları',
        ]);

        $currency = Currency::query()->firstOrFail();

        $this->actingAs($user)->put(route('admin.currencies.update', $currency), [
            'name' => 'ABD Doları',
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'ABD Doları',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.currencies.destroy', $currency))
            ->assertRedirect();

        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id,
        ]);
    }
}
