<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityControl extends Model
{
    use HasFactory;

    protected $table = 'quality_control';

    protected $fillable = [
        'production_plan_id',
        'product_id',
        'total_quantity',
        'passed_quantity',
        'failed_quantity',
        'inspector_id',
        'inspection_date',
        'notes',
        'status',
        'synced_to_logistics',
        'synced_at',
        'defect_notes',
        'corrective_actions',
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'total_quantity' => 'integer',
        'passed_quantity' => 'integer',
        'failed_quantity' => 'integer',
        'synced_at' => 'datetime',
        'synced_to_logistics' => 'boolean',
    ];

    protected $appends = [
        'pass_rate',
        'fail_rate',
        'status_label',
        'inspector_name',
        'sync_status_label',
    ];

    // Relationships
    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    // Accessors
    public function getPassRateAttribute()
    {
        if ($this->total_quantity > 0) {
            return round(($this->passed_quantity / $this->total_quantity) * 100, 2);
        }
        return 0;
    }

    public function getFailRateAttribute()
    {
        if ($this->total_quantity > 0) {
            return round(($this->failed_quantity / $this->total_quantity) * 100, 2);
        }
        return 0;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu Inspeksi',
            'in_progress' => 'Sedang Diinspeksi',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getInspectorNameAttribute()
    {
        return $this->inspector ? $this->inspector->name : '-';
    }

    public function getSyncStatusLabelAttribute()
    {
        return $this->synced_to_logistics ? 'Sudah Disinkronkan' : 'Belum Disinkronkan';
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeNotSynced($query)
    {
        return $query->where(function($q) {
            $q->whereNull('synced_to_logistics')
              ->orWhere('synced_to_logistics', false);
        });
    }

    public function scopeReadyToSync($query)
    {
        return $query->completed()
            ->where('passed_quantity', '>', 0)
            ->notSynced();
    }
}