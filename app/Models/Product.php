<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'sku',
        'barcode',
        'category_id',
        'track_stock',
        'critical_stock_level',
        'default_buy_price',
        'default_sell_price',
        'currency_code',
        'notes',
    ];

    protected $casts = [
        'track_stock' => 'boolean',
        'default_buy_price' => 'decimal:2',
        'default_sell_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function inventoryBalances()
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
