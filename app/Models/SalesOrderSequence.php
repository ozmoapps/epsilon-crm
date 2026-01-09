<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderSequence extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = 'year';

    protected $fillable = [
        'year',
        'last_number',
    ];
}
