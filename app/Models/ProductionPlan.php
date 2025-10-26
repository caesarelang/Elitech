<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'target_date',
        'priority',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'deadline',
        'production_started_at',
        'completed_at',
        'progress_percentage',
        'produced_quantity',
        'progress_notes',
        'submitted_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'approved_at' => 'datetime',
        'deadline' => 'datetime',
        'production_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'progress_percentage' => 'integer',
        'quantity' => 'integer',
        'produced_quantity' => 'integer',
    ];

    protected $appends = [
        'is_overdue',
        'status_label',
        'priority_label',
        'created_by_name',
        'approved_by_name',
    ];

public function getCreatedByNameAttribute()
{
    return $this->createdBy ? $this->createdBy->name : null;
}

public function getApprovedByNameAttribute()
{
    return $this->approvedBy ? $this->approvedBy->name : null;
}
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        if ($this->status === 'in_progress' && $this->deadline) {
            return now()->isAfter($this->deadline);
        }
        return false;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'in_progress' => 'Sedang Diproses',
            'completed' => 'Selesai',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
        ];

        return $labels[$this->priority] ?? $this->priority;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'in_progress')
            ->where('deadline', '<', now());
    }
    public function qualityControl()
    {
        return $this->hasOne(QualityControl::class);
    }
}