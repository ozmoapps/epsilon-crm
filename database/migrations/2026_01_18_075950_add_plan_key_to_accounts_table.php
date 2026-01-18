<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('plan_key')->nullable()->after('plan_id')->index();
        });

        // Safe Backfill
        $accounts = DB::table('accounts')->get();
        foreach ($accounts as $account) {
            $key = 'enterprise'; // Default
            
            if ($account->plan_id) {
                $plan = DB::table('plans')->where('id', $account->plan_id)->first();
                if ($plan && !empty($plan->key)) {
                    $key = $plan->key;
                }
            }
            
            DB::table('accounts')->where('id', $account->id)->update(['plan_key' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('plan_key');
        });
    }
};
