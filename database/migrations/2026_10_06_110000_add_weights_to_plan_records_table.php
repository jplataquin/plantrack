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
        Schema::table('plan_records', function (Blueprint $table) {
            $table->decimal('target_weight', 5, 2)->default(60.00)->after('end_date');
            $table->decimal('resource_weight', 5, 2)->default(20.00)->after('target_weight');
            $table->decimal('risk_weight', 5, 2)->default(20.00)->after('resource_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_records', function (Blueprint $table) {
            $table->dropColumn(['target_weight', 'resource_weight', 'risk_weight']);
        });
    }
};
