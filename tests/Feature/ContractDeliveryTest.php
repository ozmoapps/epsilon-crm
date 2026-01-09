<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractAttachment;
use App\Models\ContractDelivery;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ContractDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_upload_download_delete_are_authorized(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $owner->id]);

        $contract = $this->createContract($owner, $salesOrder);
        $file = UploadedFile::fake()->create('signed.pdf', 10, 'application/pdf');

        $this->actingAs($other)
            ->post(route('contracts.attachments.store', $contract), [
                'title' => 'Yetkisiz',
                'type' => 'signed_pdf',
                'file' => $file,
            ])
            ->assertForbidden();

        $response = $this->actingAs($owner)->post(route('contracts.attachments.store', $contract), [
            'title' => 'İmzalı Sözleşme',
            'type' => 'signed_pdf',
            'file' => $file,
        ]);

        $response->assertRedirect();

        $attachment = ContractAttachment::query()->first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->path);

        $this->actingAs($other)
            ->get(route('contracts.attachments.download', [$contract, $attachment]))
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('contracts.attachments.destroy', [$contract, $attachment]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('contracts.attachments.download', [$contract, $attachment]))
            ->assertOk();

        $this->actingAs($owner)
            ->delete(route('contracts.attachments.destroy', [$contract, $attachment]))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_delivery_prepared_and_marked_sent(): void
    {
        $owner = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $owner->id]);
        $contract = $this->createContract($owner, $salesOrder);

        $response = $this->actingAs($owner)->post(route('contracts.deliveries.store', $contract), [
            'channel' => 'email',
            'recipient_name' => 'Ali Veli',
            'recipient' => 'ali@example.com',
            'message' => 'Test mesajı',
            'included_pdf' => true,
            'included_attachments' => false,
        ]);

        $response->assertRedirect();

        $delivery = ContractDelivery::query()->first();
        $this->assertNotNull($delivery);
        $this->assertSame('prepared', $delivery->status);

        $this->actingAs($owner)
            ->patch(route('contracts.deliveries.mark_sent', [$contract, $delivery]))
            ->assertRedirect();

        $this->assertDatabaseHas('contract_deliveries', [
            'id' => $delivery->id,
            'status' => 'sent',
        ]);
    }

    public function test_delivery_pack_zip_downloads(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['created_by' => $owner->id]);
        $contract = $this->createContract($owner, $salesOrder);

        $file = UploadedFile::fake()->create('signed.pdf', 10, 'application/pdf');
        $path = $file->store('contracts/' . $contract->id . '/attachments', 'public');

        ContractAttachment::create([
            'contract_id' => $contract->id,
            'title' => 'İmzalı Sözleşme',
            'type' => 'signed_pdf',
            'disk' => 'public',
            'path' => $path,
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('contracts.delivery_pack', $contract));

        $response->assertOk();
        $this->assertNotNull($response->getFile());
        $this->assertGreaterThan(0, $response->getFile()->getSize());

        $zip = new ZipArchive();
        $zip->open($response->getFile()->getPathname());

        $this->assertNotFalse($zip->locateName($contract->contract_no . '.pdf'));

        $foundAttachment = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'attachments/')) {
                $foundAttachment = true;
                break;
            }
        }

        $zip->close();

        $this->assertTrue($foundAttachment);
    }

    private function createContract(User $user, SalesOrder $salesOrder): Contract
    {
        $salesOrder->loadMissing('customer');

        return Contract::create([
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
    }
}
