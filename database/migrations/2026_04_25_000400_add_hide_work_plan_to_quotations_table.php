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
        if (! Schema::hasColumn('quotations', 'hide_work_plan')) {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->boolean('hide_work_plan')->default(true)->after('work_start_date');
            });
        }

        DB::table('quotations')
            ->whereNotNull('work_end_date')
            ->orWhereNotNull('estimated_hours')
            ->orWhereNotNull('estimated_days')
            ->orWhereNotNull('hours_per_day')
            ->orWhereIn('id', DB::table('quotation_work_sections')->select('quotation_id'))
            ->update(['hide_work_plan' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('quotations', 'hide_work_plan')) {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->dropColumn('hide_work_plan');
            });
        }
    }
};
