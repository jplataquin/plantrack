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
        Schema::create('risk_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_record_id')->constrained('plan_records')->cascadeOnDelete();
            $table->text('risk');
            $table->text('impact');
            $table->text('mitigation');
            $table->enum('status', ['Hit', 'Missed', 'Void'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_management');
    }
};
