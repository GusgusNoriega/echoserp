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
        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'sales_advisor_id')) {
                $table->foreignId('sales_advisor_id')
                    ->nullable()
                    ->after('currency_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('quotations', 'is_event')) {
                $table->boolean('is_event')->default(false)->after('work_end_date');
            }

            if (! Schema::hasColumn('quotations', 'event_dates')) {
                $table->json('event_dates')->nullable()->after('is_event');
            }

            if (! Schema::hasColumn('quotations', 'event_setup')) {
                $table->date('event_setup')->nullable()->after('event_dates');
            }

            if (! Schema::hasColumn('quotations', 'event_teardown')) {
                $table->date('event_teardown')->nullable()->after('event_setup');
            }

            if (! Schema::hasColumn('quotations', 'event_location')) {
                $table->string('event_location', 500)->nullable()->after('event_teardown');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (Schema::hasColumn('quotations', 'sales_advisor_id')) {
                $table->dropConstrainedForeignId('sales_advisor_id');
            }

            foreach (['event_location', 'event_teardown', 'event_setup', 'event_dates', 'is_event'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
