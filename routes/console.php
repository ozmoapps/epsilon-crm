<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\User;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Contract;
use App\Models\WorkOrder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


