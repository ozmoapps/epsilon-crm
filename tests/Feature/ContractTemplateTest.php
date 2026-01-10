<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Services\ContractTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_template_management(): void
    {
        $this->get(route('admin.contract-templates.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.contract-templates.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_template_index(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get(route('admin.contract-templates.index'))
            ->assertOk();
    }

    public function test_authorized_user_can_create_template(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post(route('admin.contract-templates.store'), [
            'name' => 'Test Şablon',
            'locale' => 'tr',
            'format' => 'html',
            'content' => '<p>{{contract.contract_no}}</p>',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_templates', [
            'name' => 'Test Şablon',
            'locale' => 'tr',
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('contract_template_versions', [
            'version' => 1,
            'content' => '<p>{{contract.contract_no}}</p>',
            'format' => 'html',
        ]);
    }

    public function test_only_one_default_per_locale_is_kept(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $first = ContractTemplate::factory()->create([
            'name' => 'Şablon A',
            'locale' => 'tr',
            'is_default' => true,
        ]);

        $second = ContractTemplate::factory()->create([
            'name' => 'Şablon B',
            'locale' => 'tr',
            'is_default' => false,
        ]);

        $this->actingAs($user)->post(route('admin.contract-templates.make_default', $second))
            ->assertRedirect();

        $this->assertDatabaseHas('contract_templates', [
            'id' => $first->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('contract_templates', [
            'id' => $second->id,
            'is_default' => true,
        ]);
    }

    public function test_renderer_replaces_placeholders(): void
    {
        $contract = $this->createContract();
        SalesOrderItem::factory()->create(['sales_order_id' => $contract->sales_order_id]);

        $template = ContractTemplate::factory()->create([
            'content' => '<p>{{contract.contract_no}} {{customer.name}} {{totals.grand_total}} {{currency}}</p>{{line_items_table}}',
            'locale' => 'tr',
        ]);

        $renderer = app(ContractTemplateRenderer::class);
        $output = $renderer->render($contract, $template);

        $this->assertStringContainsString($contract->contract_no, $output);
        $this->assertStringContainsString($contract->customer_name, $output);
        $this->assertStringNotContainsString('{{contract.contract_no}}', $output);
    }

    public function test_contract_uses_locale_default_template_when_none_selected(): void
    {
        $user = User::factory()->create();
        $contract = $this->createContract(['created_by' => $user->id]);

        ContractTemplate::factory()->create([
            'name' => 'Varsayılan',
            'locale' => 'tr',
            'content' => '<p>Varsayılan Şablon</p>',
            'is_default' => true,
        ]);

        $this->actingAs($user)->patch(route('contracts.mark_sent', $contract))
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
        ]);
        $contract->refresh();
        $this->assertStringContainsString('Varsayılan Şablon', $contract->rendered_body);
        $this->assertNotNull($contract->contract_template_version_id);
    }

    public function test_sent_contract_keeps_snapshot_when_template_changes(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $contract = $this->createContract(['created_by' => $user->id]);

        $template = ContractTemplate::factory()->create([
            'name' => 'Varsayılan',
            'locale' => 'tr',
            'content' => '<p>İlk Sürüm</p>',
            'is_default' => true,
        ]);

        $this->actingAs($user)->patch(route('contracts.mark_sent', $contract))
            ->assertRedirect();

        $contract->refresh();
        $this->assertStringContainsString('İlk Sürüm', $contract->rendered_body);
        $originalVersionId = $contract->contract_template_version_id;


        $this->actingAs($user)->put(route('admin.contract-templates.update', $template), [
            'name' => $template->name,
            'locale' => $template->locale,
            'format' => $template->format,
            'content' => '<p>Yeni Sürüm</p>',
            'is_default' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($user)->get(route('contracts.pdf', $contract))
            ->assertStatus(200);

        $contract->refresh();
        $this->assertStringContainsString('İlk Sürüm', $contract->rendered_body);
        $this->assertSame($originalVersionId, $contract->contract_template_version_id);
    }

    public function test_pdf_route_uses_rendered_body_if_present(): void
    {
        $user = User::factory()->create();
        $contract = $this->createContract([
            'created_by' => $user->id,
            'rendered_body' => '<p>Hazır içerik</p>',
        ]);

        $response = $this->actingAs($user)->get(route('contracts.pdf', $contract));

        $response->assertStatus(200);
        $response->assertSee('Hazır içerik', false);
    }

    public function test_template_update_creates_new_version_and_bumps_current(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $template = ContractTemplate::factory()->create([
            'content' => '<p>İlk içerik</p>',
            'format' => 'html',
        ]);

        $currentVersionId = $template->current_version_id;

        $this->actingAs($user)->put(route('admin.contract-templates.update', $template), [
            'name' => $template->name,
            'locale' => $template->locale,
            'format' => 'html',
            'content' => '<p>Yeni içerik</p>',
            'is_default' => false,
            'is_active' => true,
            'change_note' => 'Güncelleme',
        ])->assertRedirect();

        $template->refresh();

        $this->assertNotEquals($currentVersionId, $template->current_version_id);
        $this->assertDatabaseHas('contract_template_versions', [
            'contract_template_id' => $template->id,
            'content' => '<p>Yeni içerik</p>',
            'change_note' => 'Güncelleme',
        ]);
    }

    public function test_restore_creates_new_version_from_selected(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $template = ContractTemplate::factory()->create([
            'content' => '<p>İlk içerik</p>',
            'format' => 'html',
        ]);

        $template->createVersion('<p>İkinci içerik</p>', 'html', $user->id, 'Yeni sürüm');
        $versionToRestore = $template->versions()->orderBy('version')->first();

        $this->actingAs($user)->post(route('admin.contract-templates.versions.restore', [$template, $versionToRestore]))
            ->assertRedirect();

        $template->refresh();

        $this->assertDatabaseHas('contract_template_versions', [
            'contract_template_id' => $template->id,
            'content' => $versionToRestore->content,
        ]);
        $this->assertNotEquals($versionToRestore->id, $template->current_version_id);
    }

    private function createContract(array $overrides = []): Contract
    {
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->load('customer');
        $creatorId = User::factory()->create()->id;

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
            'created_by' => $creatorId,
        ], $overrides));
    }
}
