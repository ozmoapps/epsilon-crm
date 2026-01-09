<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractDelivery;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use App\Services\ContractTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ContractDeliveryController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'channel' => ['required', 'string', 'max:30', 'in:email,whatsapp,manual'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'included_pdf' => ['sometimes', 'boolean'],
            'included_attachments' => ['sometimes', 'boolean'],
        ], [
            'channel.required' => 'Gönderim kanalı zorunludur.',
            'channel.in' => 'Gönderim kanalı geçersiz.',
            'recipient_name.max' => 'Alıcı adı en fazla 255 karakter olabilir.',
            'recipient.max' => 'Alıcı bilgisi en fazla 255 karakter olabilir.',
        ]);

        $contract->deliveries()->create([
            'channel' => $validated['channel'],
            'recipient_name' => $validated['recipient_name'] ?? null,
            'recipient' => $validated['recipient'] ?? null,
            'message' => $validated['message'] ?? null,
            'included_pdf' => $request->boolean('included_pdf', true),
            'included_attachments' => $request->boolean('included_attachments'),
            'status' => 'prepared',
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Gönderim paketi hazırlandı.');
    }

    public function markSent(Request $request, Contract $contract, ContractDelivery $delivery)
    {
        $this->authorize('update', $contract);

        if ($delivery->contract_id !== $contract->id) {
            abort(404);
        }

        $delivery->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Gönderim gönderildi olarak işaretlendi.');
    }

    public function downloadPack(Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! class_exists(ZipArchive::class)) {
            return back()->with('warning', 'Zip desteği bulunamadı.');
        }

        $contract->load(['salesOrder.customer', 'salesOrder.items', 'attachments']);
        $this->ensureRendered($contract);

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir . '/' . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return back()->with('warning', 'Zip dosyası oluşturulamadı.');
        }

        $pdfPath = $tmpDir . '/' . Str::uuid() . '.pdf';
        file_put_contents($pdfPath, view('contracts.pdf', ['contract' => $contract])->render());

        $pdfName = $contract->contract_no . '.pdf';
        $zip->addFile($pdfPath, $pdfName);

        foreach ($contract->attachments as $attachment) {
            $disk = Storage::disk($attachment->disk);
            if (! $disk->exists($attachment->path)) {
                continue;
            }

            $extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
            $slug = Str::slug($attachment->title) ?: 'ek-dosya';
            $attachmentName = $attachment->id . '-' . $slug . ($extension ? '.' . $extension : '');
            $zip->addFile($disk->path($attachment->path), 'attachments/' . $attachmentName);
        }

        $zip->close();
        unlink($pdfPath);

        $downloadName = $contract->contract_no . '_delivery_pack.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    private function ensureRendered(Contract $contract): void
    {
        if (! $contract->rendered_body) {
            $this->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }
    }

    private function applyTemplate(Contract $contract, bool $setRenderedAt = false): void
    {
        $template = $contract->contractTemplate
            ?: ContractTemplate::defaultForLocale($contract->locale);

        if (! $template) {
            return;
        }

        $renderer = app(ContractTemplateRenderer::class);
        $version = $this->resolveTemplateVersion($contract, $template);

        if (! $version) {
            return;
        }

        $contract->forceFill([
            'rendered_body' => $renderer->render($contract, $version),
            'rendered_at' => $setRenderedAt ? now() : $contract->rendered_at,
            'contract_template_version_id' => $version->id,
        ])->save();
    }

    private function resolveTemplateVersion(Contract $contract, ContractTemplate $template): ?ContractTemplateVersion
    {
        if ($contract->contract_template_version_id) {
            return ContractTemplateVersion::query()->find($contract->contract_template_version_id);
        }

        $template->loadMissing('currentVersion');

        return $template->currentVersion ?: $template->latestVersion();
    }
}
