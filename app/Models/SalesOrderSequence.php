<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderSequence extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = 'year';

    protected $fillable = [
        'tenant_id',
        'year',
        'last_number',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('tenant_id', $this->getAttribute('tenant_id'))
                     ->where('year', $this->getAttribute('year'));
    }
}
