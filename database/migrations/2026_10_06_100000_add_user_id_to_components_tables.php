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
            $table->foreignId('user_id')->nullable()->after('plan_record_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('plan_record_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('risk_management', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('plan_record_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('target_objectives', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('risk_management', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
