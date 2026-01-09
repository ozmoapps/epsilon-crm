<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractAttachmentController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $maxSize = config('contracts.attachments.max_size_kb', 10240);
        $allowedMimes = config('contracts.attachments.mimes', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'max:' . $maxSize, 'mimes:' . implode(',', $allowedMimes)],
        ], [
            'title.required' => 'Dosya başlığı zorunludur.',
            'title.max' => 'Dosya başlığı en fazla 255 karakter olabilir.',
            'type.required' => 'Dosya türü seçilmelidir.',
            'file.required' => 'Yüklenecek dosya zorunludur.',
            'file.file' => 'Yüklenen içerik dosya olmalıdır.',
            'file.max' => 'Dosya boyutu en fazla ' . $maxSize . ' KB olabilir.',
            'file.mimes' => 'Dosya formatı geçersiz.',
        ]);

        $disk = config('contracts.attachments.disk', 'public');
        $file = $validated['file'];
        $path = $file->store('contracts/' . $contract->id . '/attachments', $disk);

        $contract->attachments()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Ek dosya yüklendi.');
    }

    public function download(Contract $contract, ContractAttachment $attachment)
    {
        $this->authorize('update', $contract);

        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        $extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
        $filename = Str::slug($attachment->title) ?: 'ek-dosya';
        $downloadName = $extension ? $filename . '.' . $extension : $filename;

        return $disk->download($attachment->path, $downloadName);
    }

    public function destroy(Contract $contract, ContractAttachment $attachment)
    {
        $this->authorize('update', $contract);

        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Ek dosya silindi.');
    }
}
