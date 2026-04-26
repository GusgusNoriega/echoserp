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
            if (! Schema::hasColumn('quotation_items', 'unit_label')) {
                $table->string('unit_label', 50)->nullable()->after('description');
            }
        });

        Schema::create('quotation_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_document_label', 50)->default('RUC');
            $table->string('company_document_number', 50)->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone', 50)->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_address', 500)->nullable();
            $table->string('number_prefix', 20)->default('COT');
            $table->unsignedInteger('default_validity_days')->default(15);
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->text('default_notes')->nullable();
            $table->text('default_terms')->nullable();
            $table->string('default_signer_name')->nullable();
            $table->string('default_signer_title')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('status', 30)->default('draft');
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('client_company_name');
            $table->string('client_document_label', 50)->default('RUC');
            $table->string('client_document_number', 50)->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone', 50)->nullable();
            $table->string('client_address', 500)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->date('work_start_date')->nullable();
            $table->boolean('hide_work_plan')->default(true);
            $table->date('work_end_date')->nullable();
            $table->decimal('estimated_hours', 10, 2)->nullable();
            $table->decimal('estimated_days', 10, 2)->nullable();
            $table->decimal('hours_per_day', 10, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('issuer_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'issue_date']);
        });

        Schema::create('quotation_line_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('source_type', 20)->default('manual');
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit_label', 50)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
        });

        Schema::create('quotation_work_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('title');
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
        });

        Schema::create('quotation_work_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_work_section_id')->constrained('quotation_work_sections')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('duration_days', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['quotation_work_section_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_work_tasks');
        Schema::dropIfExists('quotation_work_sections');
        Schema::dropIfExists('quotation_line_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('quotation_settings');

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('quotation_items', 'unit_label')) {
                $table->dropColumn('unit_label');
            }
        });
    }
};
