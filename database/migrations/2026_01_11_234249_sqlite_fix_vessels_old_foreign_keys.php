<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sadece SQLite için çalış
        if (DB::connection()->getDriverName() !== 'sqlite' || app()->runningUnitTests()) {
            return;
        }

        // Foreign key constraint'leri geçici olarak kapat
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            // vessels_old referansı içeren tabloları bul
            $tables = DB::select(
                "SELECT name, sql FROM sqlite_master 
                 WHERE type='table' 
                   AND sql IS NOT NULL 
                   AND sql LIKE '%vessels_old%'"
            );

            foreach ($tables as $tableInfo) {
                $table = $tableInfo->name;
                $oldSql = $tableInfo->sql;

                // Geçici tablo adı
                $oldName = "{$table}__fkfix_old";

                // 1. Mevcut tabloyu yedek adla rename et
                DB::statement("ALTER TABLE \"{$table}\" RENAME TO \"{$oldName}\"");

                // 2. vessels_old referanslarını vessels ile değiştir
                $newSql = str_replace('vessels_old', 'vessels', $oldSql);

                // 3. Düzeltilmiş CREATE TABLE ile yeni tabloyu oluştur
                DB::statement($newSql);

                // 4. Kolon listesini al
                $columns = DB::select("PRAGMA table_info(\"{$oldName}\")");
                $columnNames = array_map(fn($col) => "\"{$col->name}\"", $columns);
                $columnList = implode(', ', $columnNames);

                // 5. Verileri eski tablodan yeni tabloya kopyala
                DB::statement(
                    "INSERT INTO \"{$table}\" ({$columnList}) 
                     SELECT {$columnList} FROM \"{$oldName}\""
                );

                // 6. Eski tabloyu sil
                DB::statement("DROP TABLE \"{$oldName}\"");
            }
        } finally {
            // Foreign key constraint'leri tekrar aç
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri almaya gerek yok - vessels_old zaten eski bir referans
    }
};
