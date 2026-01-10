<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_company_profiles(): void
    {
        $this->get(route('company-profiles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_company_profile_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('company-profiles.index'))
            ->assertOk();
    }

    public function test_authenticated_user_can_view_company_profile_edit_page(): void
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create();

        $this->actingAs($user)
            ->get(route('company-profiles.edit', $companyProfile))
            ->assertOk();
    }

    public function test_authenticated_user_can_patch_company_profile(): void
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'name' => 'Epsilon Denizcilik',
            'email' => 'info@epsilon.test',
        ]);

        $this->actingAs($user)
            ->patch(route('company-profiles.update', $companyProfile), [
                'name' => 'Epsilon Servis',
                'address' => 'İzmir',
                'phone' => '+90 555 000 11 11',
                'email' => 'servis@epsilon.test',
                'tax_no' => '0987654321',
                'footer_text' => 'Epsilon Servis · İzmir',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('company_profiles', [
            'id' => $companyProfile->id,
            'name' => 'Epsilon Servis',
            'email' => 'servis@epsilon.test',
        ]);
    }

    public function test_authenticated_user_can_create_update_and_delete_company_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('company-profiles.store'), [
            'name' => 'Epsilon Denizcilik',
            'address' => 'İstanbul',
            'phone' => '+90 555 000 00 00',
            'email' => 'info@epsilon.test',
            'tax_no' => '1234567890',
            'footer_text' => 'Epsilon Denizcilik · İstanbul',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('company_profiles', [
            'name' => 'Epsilon Denizcilik',
            'email' => 'info@epsilon.test',
        ]);

        $companyProfile = CompanyProfile::query()->firstOrFail();

        $this->actingAs($user)->put(route('company-profiles.update', $companyProfile), [
            'name' => 'Epsilon Servis',
            'address' => 'İzmir',
            'phone' => '+90 555 000 11 11',
            'email' => 'servis@epsilon.test',
            'tax_no' => '0987654321',
            'footer_text' => 'Epsilon Servis · İzmir',
        ])->assertRedirect();

        $this->assertDatabaseHas('company_profiles', [
            'id' => $companyProfile->id,
            'name' => 'Epsilon Servis',
            'email' => 'servis@epsilon.test',
        ]);

        $this->actingAs($user)
            ->delete(route('company-profiles.destroy', $companyProfile))
            ->assertRedirect();

        $this->assertDatabaseMissing('company_profiles', [
            'id' => $companyProfile->id,
        ]);
    }
}
