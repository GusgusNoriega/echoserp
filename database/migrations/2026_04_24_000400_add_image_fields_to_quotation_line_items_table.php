<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('description');
            $table->string('image_source', 20)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_source']);
        });
    }
};
