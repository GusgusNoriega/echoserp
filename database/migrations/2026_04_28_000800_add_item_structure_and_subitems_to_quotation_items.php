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
        Schema::table('quotation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_items', 'item_structure')) {
                $table->string('item_structure', 20)->default('single')->after('type');
            }
        });

        if (! Schema::hasTable('quotation_item_sub_items')) {
            Schema::create('quotation_item_sub_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('quotation_item_id')->constrained('quotation_items')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(1);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('unit_label', 50)->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->timestamps();

                $table->index(['quotation_item_id', 'sort_order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_item_sub_items');

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('quotation_items', 'item_structure')) {
                $table->dropColumn('item_structure');
            }
        });
    }
};
