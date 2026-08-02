<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_locations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('retail_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('package_label_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('fish_batch_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('retailer_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('retail_location_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('received_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('received_package_count');
            $table->decimal('received_weight_kg', 12, 3);
            $table->decimal('condition_temperature', 7, 3)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('received_at');
            $table->timestamps();
            $table->index(['retailer_organization_id', 'received_at']);
        });

        Schema::create('inventory_lots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('retail_receipt_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('package_label_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('fish_batch_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('retail_location_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->unsignedInteger('total_packages');
            $table->unsignedInteger('available_packages');
            $table->unsignedInteger('reserved_packages')->default(0);
            $table->unsignedInteger('sold_packages')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['organization_id', 'retail_location_id', 'status'], 'inventory_org_location_status_idx');
        });

        Schema::create('retail_sales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('retail_location_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('sold_by')->constrained('users')->restrictOnDelete();
            $table->uuid('client_reference');
            $table->string('receipt_number')->unique();
            $table->string('status')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->dateTime('sold_at');
            $table->timestamps();
            $table->unique(['organization_id', 'client_reference']);
            $table->index(['organization_id', 'sold_at']);
        });

        Schema::create('retail_sale_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('retail_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_lot_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->unique(['retail_sale_id', 'inventory_lot_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_lot_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type')->index();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('resulting_available');
            $table->unsignedInteger('resulting_reserved');
            $table->unsignedInteger('resulting_sold');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['inventory_lot_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        foreach (['stock_movements', 'retail_sale_items', 'retail_sales', 'inventory_lots', 'retail_receipts', 'retail_locations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
