<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logistics extends Model
{
    use HasFactory;

    protected $table = 'logistics';

    protected $fillable = [
        'product_id',
        'warehouse_name',
        'warehouse_location',
        'stock',
        'minimum_stock',
        'last_updated_by',
        'notes',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
    ];

    protected $appends = [
        'product_name',
        'product_sku',
        'stock_status',
        'updater_name',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // Accessors
    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : null;
    }

    public function getProductSkuAttribute()
    {
        return $this->product ? $this->product->sku : null;
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock <= $this->minimum_stock) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusLabelAttribute()
    {
        $labels = [
            'out_of_stock' => 'Habis',
            'low_stock' => 'Stok Menipis',
            'in_stock' => 'Tersedia',
        ];

        return $labels[$this->stock_status] ?? 'Unknown';
    }

    public function getUpdaterNameAttribute()
    {
        return $this->updater ? $this->updater->name : null;
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= minimum_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeByWarehouse($query, $warehouseName)
    {
        return $query->where('warehouse_name', $warehouseName);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}