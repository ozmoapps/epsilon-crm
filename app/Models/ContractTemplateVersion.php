<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_template_id',
        'version',
        'content',
        'format',
        'change_note',
        'created_by',
    ];

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
