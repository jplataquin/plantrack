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
        Schema::table('target_objectives', function (Blueprint $table) {
            $table->string('actual')->nullable()->after('quantity');
            $table->string('unit')->nullable()->after('actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('target_objectives', function (Blueprint $table) {
            $table->dropColumn(['actual', 'unit']);
        });
    }
};
