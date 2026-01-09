<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'channel',
        'recipient_name',
        'recipient',
        'message',
        'included_pdf',
        'included_attachments',
        'status',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'included_pdf' => 'boolean',
        'included_attachments' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
