<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medical extends Model
{
    protected $fillable = ['reqid', 'empnum', 'empname', 'receiptNumber', 'amount', 'transactionDate', 'employeeNotes', 'status', 'payout', 'adminNotes', 'processed'];

    public function images()
    {
        return $this->hasMany(MedicalImage::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status', 'id');
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->receipt_path ? asset('storage/' . $this->receipt_path) : null;
    }
}
