<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('quotations', 'event_setup') || ! Schema::hasColumn('quotations', 'event_teardown')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE quotations SET event_setup = NULL WHERE event_setup IS NOT NULL AND event_setup NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'");
        DB::statement("UPDATE quotations SET event_teardown = NULL WHERE event_teardown IS NOT NULL AND event_teardown NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'");
        DB::statement('ALTER TABLE quotations MODIFY event_setup DATE NULL, MODIFY event_teardown DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'event_setup') || ! Schema::hasColumn('quotations', 'event_teardown')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE quotations MODIFY event_setup VARCHAR(255) NULL, MODIFY event_teardown VARCHAR(255) NULL');
    }
};
