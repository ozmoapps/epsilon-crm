<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('recipient_name')->nullable();
            $table->string('recipient')->nullable();
            $table->longText('message')->nullable();
            $table->boolean('included_pdf')->default(true);
            $table->boolean('included_attachments')->default(false);
            $table->string('status');
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_deliveries');
    }
};
