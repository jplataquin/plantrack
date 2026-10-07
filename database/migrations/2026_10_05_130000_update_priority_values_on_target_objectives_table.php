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
        // Standardize existing priority values to low, high, critical
        DB::table('target_objectives')->whereIn('priority', ['Low', 'low'])->update(['priority' => 'low']);
        DB::table('target_objectives')->whereIn('priority', ['High', 'high'])->update(['priority' => 'high']);
        DB::table('target_objectives')->whereIn('priority', ['Urgent', 'urgent', 'Critical', 'critical'])->update(['priority' => 'critical']);
        DB::table('target_objectives')->whereIn('priority', ['Medium', 'medium'])->orWhereNull('priority')->update(['priority' => 'low']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('target_objectives')->where('priority', 'low')->update(['priority' => 'Low']);
        DB::table('target_objectives')->where('priority', 'high')->update(['priority' => 'High']);
        DB::table('target_objectives')->where('priority', 'critical')->update(['priority' => 'Urgent']);
    }
};
