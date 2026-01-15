<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            return;
        }

        $cols = Schema::getColumnListing('currencies');

        $codeCol      = $this->pick($cols, ['code', 'currency_code', 'iso_code']);
        $nameCol      = $this->pick($cols, ['name', 'title', 'label']);
        $symbolCol    = $this->pick($cols, ['symbol', 'sign']);
        $isActiveCol  = $this->pick($cols, ['is_active', 'active', 'enabled']);
        $precisionCol = $this->pick($cols, ['precision', 'decimal_places']);

        $hasCreatedAt = in_array('created_at', $cols, true);
        $hasUpdatedAt = in_array('updated_at', $cols, true);

        // Code kolonunu bulamazsak güvenli şekilde hiçbir şey yapmayalım.
        if (!$codeCol) {
            return;
        }

        $now = Carbon::now();

        $defaults = [
            ['code' => 'TRY', 'name' => 'Türk Lirası',      'symbol' => '₺'],
            ['code' => 'USD', 'name' => 'Amerikan Doları',  'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro',            'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'İngiliz Sterlini', 'symbol' => '£'],
        ];

        foreach ($defaults as $d) {
            $attrs = [$codeCol => $d['code']];

            $values = [];
            if ($nameCol)      $values[$nameCol] = $d['name'];
            if ($symbolCol)    $values[$symbolCol] = $d['symbol'];
            if ($isActiveCol)  $values[$isActiveCol] = 1;
            if ($precisionCol) $values[$precisionCol] = 2;

            if ($hasCreatedAt) $values['created_at'] = $now;
            if ($hasUpdatedAt) $values['updated_at'] = $now;

            DB::table('currencies')->updateOrInsert($attrs, $values);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('currencies')) {
            return;
        }

        $cols = Schema::getColumnListing('currencies');
        $codeCol = $this->pick($cols, ['code', 'currency_code', 'iso_code']);

        if (!$codeCol) {
            return;
        }

        DB::table('currencies')
            ->whereIn($codeCol, ['TRY', 'USD', 'EUR', 'GBP'])
            ->delete();
    }

    private function pick(array $cols, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (in_array($c, $cols, true)) return $c;
        }
        return null;
    }
};
