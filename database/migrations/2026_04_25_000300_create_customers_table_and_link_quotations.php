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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('document_label', 50)->default('RUC');
            $table->string('document_number', 50)->nullable()->unique();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'company_name']);
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('summary')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (Schema::hasColumn('quotations', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });

        Schema::dropIfExists('customers');
    }
};
