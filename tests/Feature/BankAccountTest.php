<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_bank_accounts(): void
    {
        $this->get(route('bank-accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_bank_account_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('bank-accounts.index'))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_update_and_delete_bank_account(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create([
            'code' => 'TRY',
            'name' => 'Türk Lirası',
        ]);

        $response = $this->actingAs($user)->post(route('bank-accounts.store'), [
            'name' => 'Ana Hesap',
            'bank_name' => 'Epsilon Bank',
            'branch_name' => 'Merkez',
            'iban' => 'TR123456789012345678901234',
            'currency_id' => $currency->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_accounts', [
            'name' => 'Ana Hesap',
            'bank_name' => 'Epsilon Bank',
            'currency_id' => $currency->id,
        ]);

        $bankAccount = BankAccount::query()->firstOrFail();

        $this->actingAs($user)->put(route('bank-accounts.update', $bankAccount), [
            'name' => 'Güncel Hesap',
            'bank_name' => 'Epsilon Bank',
            'branch_name' => 'Yeni Şube',
            'iban' => 'TR123456789012345678901999',
            'currency_id' => $currency->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'name' => 'Güncel Hesap',
            'branch_name' => 'Yeni Şube',
        ]);

        $this->actingAs($user)
            ->delete(route('bank-accounts.destroy', $bankAccount))
            ->assertRedirect();

        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }
}
