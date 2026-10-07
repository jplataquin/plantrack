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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_record_id')->constrained('plan_records')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_objective_id')->nullable()->constrained('target_objectives')->nullOnDelete();
            $table->text('description');
            $table->string('quantity', 100);
            $table->string('unit', 50)->nullable();
            $table->string('actual', 100)->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('plan_records', function (Blueprint $table) {
            $table->decimal('budget_weight', 5, 2)->default(10.00)->after('risk_weight');
        });

        // Update default weights on existing plan records where risk was 20.00 to 10.00 risk and 10.00 budget
        DB::table('plan_records')
            ->where('risk_weight', 20.00)
            ->where('target_weight', 60.00)
            ->where('resource_weight', 20.00)
            ->update([
                'risk_weight' => 10.00,
                'budget_weight' => 10.00,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');

        Schema::table('plan_records', function (Blueprint $table) {
            $table->dropColumn('budget_weight');
        });
    }
};
