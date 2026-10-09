<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('target_objectives', function (Blueprint $table) {
            $table->double('actual')->nullable()->change();
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->double('actual')->nullable()->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->double('actual')->nullable()->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            foreach (['target_objectives', 'resources', 'budgets'] as $tableName) {
                DB::statement("
                    CREATE TRIGGER IF NOT EXISTS check_{$tableName}_actual_insert BEFORE INSERT ON {$tableName}
                    BEGIN
                        SELECT CASE
                            WHEN NEW.actual IS NOT NULL AND typeof(NEW.actual) NOT IN ('integer', 'real')
                            THEN RAISE(ABORT, 'The actual field must be numeric.')
                        END;
                    END;
                ");

                DB::statement("
                    CREATE TRIGGER IF NOT EXISTS check_{$tableName}_actual_update BEFORE UPDATE OF actual ON {$tableName}
                    BEGIN
                        SELECT CASE
                            WHEN NEW.actual IS NOT NULL AND typeof(NEW.actual) NOT IN ('integer', 'real')
                            THEN RAISE(ABORT, 'The actual field must be numeric.')
                        END;
                    END;
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (['target_objectives', 'resources', 'budgets'] as $tableName) {
                DB::statement("DROP TRIGGER IF EXISTS check_{$tableName}_actual_insert");
                DB::statement("DROP TRIGGER IF EXISTS check_{$tableName}_actual_update");
            }
        }

        Schema::table('target_objectives', function (Blueprint $table) {
            $table->string('actual')->nullable()->change();
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->string('actual')->nullable()->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->string('actual', 100)->nullable()->change();
        });
    }
};
