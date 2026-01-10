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
        $this->get(route('currencies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_currency_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('currencies.index'))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_update_and_delete_currency(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('currencies.store'), [
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

        $this->actingAs($user)->put(route('currencies.update', $currency), [
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
            ->delete(route('currencies.destroy', $currency))
            ->assertRedirect();

        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id,
        ]);
    }
}
