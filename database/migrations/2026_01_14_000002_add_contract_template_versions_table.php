<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_template_id')->constrained('contract_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->string('format')->default('html');
            $table->string('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_template_id', 'version']);
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->foreignId('current_version_id')->nullable()->after('created_by')
                ->constrained('contract_template_versions')->nullOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('contract_template_version_id')->nullable()->after('contract_template_id')
                ->constrained('contract_template_versions')->nullOnDelete();
        });

        $templates = DB::table('contract_templates')->orderBy('id')->get();

        foreach ($templates as $template) {
            $versionId = DB::table('contract_template_versions')->insertGetId([
                'contract_template_id' => $template->id,
                'version' => 1,
                'content' => $template->content,
                'format' => $template->format,
                'change_note' => null,
                'created_by' => $template->created_by,
                'created_at' => $template->created_at ?? now(),
                'updated_at' => $template->updated_at ?? now(),
            ]);

            DB::table('contract_templates')
                ->where('id', $template->id)
                ->update(['current_version_id' => $versionId]);
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_template_version_id']);
            $table->dropColumn('contract_template_version_id');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        Schema::dropIfExists('contract_template_versions');
    }
};
