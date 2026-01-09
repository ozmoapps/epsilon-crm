<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'locale',
        'content',
        'format',
        'is_default',
        'is_active',
        'created_by',
        'current_version_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions()
    {
        return $this->hasMany(ContractTemplateVersion::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(ContractTemplateVersion::class, 'current_version_id');
    }

    public function latestVersion()
    {
        return $this->versions()->orderByDesc('version')->first();
    }

    public function createVersion(string $content, string $format, ?int $userId = null, ?string $changeNote = null): ContractTemplateVersion
    {
        $nextVersion = (int) $this->versions()->max('version');
        $nextVersion = $nextVersion > 0 ? $nextVersion + 1 : 1;

        $version = $this->versions()->create([
            'version' => $nextVersion,
            'content' => $content,
            'format' => $format,
            'change_note' => $changeNote,
            'created_by' => $userId,
        ]);

        $this->forceFill([
            'current_version_id' => $version->id,
            'content' => $content,
            'format' => $format,
        ])->saveQuietly();

        return $version;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function defaultForLocale(string $locale): ?self
    {
        return self::query()
            ->active()
            ->where('locale', $locale)
            ->where('is_default', true)
            ->latest('id')
            ->first();
    }
}
