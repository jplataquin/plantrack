<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('actual')->nullable()->after('quantity');
            $table->string('unit')->nullable()->after('actual');
            $table->foreignId('target_objective_id')
                ->nullable()
                ->after('unit')
                ->constrained('target_objectives')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['target_objective_id']);
            $table->dropColumn(['target_objective_id', 'unit', 'actual']);
        });
    }
};
