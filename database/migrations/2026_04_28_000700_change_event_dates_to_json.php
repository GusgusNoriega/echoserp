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
        if (! Schema::hasColumn('quotations', 'event_dates')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('quotations', 'event_dates_json')) {
            DB::statement('ALTER TABLE quotations ADD event_dates_json JSON NULL AFTER is_event');
        }

        DB::table('quotations')
            ->whereNotNull('event_dates')
            ->select(['id', 'event_dates'])
            ->orderBy('id')
            ->get()
            ->each(function (object $quotation): void {
                $dates = $this->normalizeStoredDates($quotation->event_dates);

                DB::table('quotations')
                    ->where('id', $quotation->id)
                    ->update(['event_dates_json' => $dates === [] ? null : json_encode($dates)]);
            });

        DB::statement('ALTER TABLE quotations DROP COLUMN event_dates');
        DB::statement('ALTER TABLE quotations CHANGE event_dates_json event_dates JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'event_dates')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE quotations MODIFY event_dates TEXT NULL');
    }

    private function normalizeStoredDates(mixed $value): array
    {
        $text = trim((string) $value);

        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        $values = is_array($decoded)
            ? $decoded
            : preg_split('/\s*\/\/\s*|\r\n|\r|\n|,/', $text);

        return collect($values ?: [])
            ->map(static fn (mixed $date): string => trim((string) $date))
            ->filter(static fn (string $date): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->values()
            ->all();
    }
};
