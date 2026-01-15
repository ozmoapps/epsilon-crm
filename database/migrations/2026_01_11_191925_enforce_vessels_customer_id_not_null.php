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
        // A) Safety Check
        $nullCount = DB::table('vessels')->whereNull('customer_id')->count();
        if ($nullCount > 0) {
            throw new RuntimeException("Cannot enforce NOT NULL on vessels.customer_id: {$nullCount} records have NULL customer_id.");
        }

        $driver = DB::getDriverName();

        // B) Enforce NOT NULL
        if ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            
            // 1. Determine exact column type (e.g., 'bigint unsigned')
            $colType = DB::scalar("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = 'vessels' 
                  AND COLUMN_NAME = 'customer_id'
            ", [$dbName]);

            if (!$colType) {
                 throw new RuntimeException("Could not determine column type for vessels.customer_id");
            }

            // 2. Modify using the exact existing type
            DB::statement("ALTER TABLE vessels MODIFY customer_id {$colType} NOT NULL");

        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vessels ALTER COLUMN customer_id SET NOT NULL');

        } elseif ($driver === 'sqlite') {
            // SQLite Workaround: Rebuild table to enforce NOT NULL
            DB::statement('PRAGMA foreign_keys=OFF;');
            DB::beginTransaction();

            // 1. Rename old table
            Schema::rename('vessels', 'vessels_old');

            // 2. Create new table with exact schema but with NOT NULL constraint
            Schema::create('vessels', function (Blueprint $table) {
                $table->id();
                // Enforce NOT NULL explicitly
                $table->unsignedBigInteger('customer_id'); 
                $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
                
                $table->string('name');
                $table->string('type')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            // 3. Copy Data
            // Get columns dynamically to be safe
            $oldCols = array_map(fn($c) => $c->name, DB::select('PRAGMA table_info(vessels_old)'));
            $newCols = array_map(fn($c) => $c->name, DB::select('PRAGMA table_info(vessels)'));
            $commonCols = array_intersect($oldCols, $newCols);
            
            if (!empty($commonCols)) {
                $colsStr = implode(', ', array_map(fn($c) => '"'.$c.'"', $commonCols));
                DB::statement("INSERT INTO vessels ($colsStr) SELECT $colsStr FROM vessels_old");
            }

            // 4. Drop old table
            Schema::drop('vessels_old');

            DB::commit();
            DB::statement('PRAGMA foreign_keys=ON;');

        } else {
            throw new RuntimeException("Unsupported database driver for raw SQL alteration: {$driver}");
        }

        // C) Ensure Index and FK Exist
        if ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();

            // Check Index
            $hasIndex = DB::scalar("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = 'vessels' 
                  AND COLUMN_NAME = 'customer_id'
            ", [$dbName]) > 0;

            if (!$hasIndex) {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->index('customer_id');
                });
            }

            // Check FK
            $hasFk = DB::scalar("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = 'vessels' 
                  AND COLUMN_NAME = 'customer_id'
                  AND REFERENCED_TABLE_NAME = 'customers'
            ", [$dbName]) > 0;

            if (!$hasFk) {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
                });
            }

        } elseif ($driver === 'pgsql') {
            // Check Index
            $hasIndex = DB::scalar("
                SELECT COUNT(*)
                FROM pg_indexes
                WHERE tablename = 'vessels'
                  AND indexdef LIKE '%(customer_id)%'
            ") > 0;

             if (!$hasIndex) {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->index('customer_id');
                });
            }

            // Check FK
             $hasFk = DB::scalar("
                SELECT COUNT(*)
                FROM information_schema.table_constraints AS tc 
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY' 
                  AND tc.table_name = 'vessels' 
                  AND kcu.column_name = 'customer_id'
            ") > 0;

            if (!$hasFk) {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        // Revert NOT NULL
        if ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            
            // 1. Determine exact column type to ensure we don't accidentally change it
            $colType = DB::scalar("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = 'vessels' 
                  AND COLUMN_NAME = 'customer_id'
            ", [$dbName]);

            if ($colType) {
                DB::statement("ALTER TABLE vessels MODIFY customer_id {$colType} NULL");
            } else {
                // Fallback if something weird happened, though unlikely
                DB::statement('ALTER TABLE vessels MODIFY customer_id BIGINT UNSIGNED NULL');
            }

        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vessels ALTER COLUMN customer_id DROP NOT NULL');

        } elseif ($driver === 'sqlite') {
             // SQLite Workaround: Rebuild table to allow NULL again
            DB::statement('PRAGMA foreign_keys=OFF;');
            DB::beginTransaction();

            Schema::rename('vessels', 'vessels_old');

            Schema::create('vessels', function (Blueprint $table) {
                $table->id();
                // Allow NULL again
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();

                $table->string('name');
                $table->string('type')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            $oldCols = array_map(fn($c) => $c->name, DB::select('PRAGMA table_info(vessels_old)'));
            $newCols = array_map(fn($c) => $c->name, DB::select('PRAGMA table_info(vessels)'));
            $commonCols = array_intersect($oldCols, $newCols);
            
            if (!empty($commonCols)) {
                $colsStr = implode(', ', array_map(fn($c) => '"'.$c.'"', $commonCols));
                DB::statement("INSERT INTO vessels ($colsStr) SELECT $colsStr FROM vessels_old");
            }

            Schema::drop('vessels_old');

            DB::commit();
            DB::statement('PRAGMA foreign_keys=ON;');
        }

        // Note: We are purposely NOT removing the FK/Index in down() 
        // because we only "ensured" them, we don't know if they were there before.
        // Removing them might break the schema if they were original.
        // Reverting NOT NULL is sufficient for "down".
    }
};
