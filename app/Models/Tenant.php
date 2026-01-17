<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'domain', 'is_active'];

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $slug = \Illuminate\Support\Str::slug($tenant->name);
                if (empty($slug)) {
                    $slug = 'tenant-' . uniqid();
                }
                
                // Simple collision resolution
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $count++;
                    $slug = $originalSlug . '-' . $count;
                }
                
                $tenant->slug = $slug;
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function plan()
    {
        return $this->hasOneThrough(Plan::class, Account::class, 'id', 'id', 'account_id', 'plan_id');
    }
}
