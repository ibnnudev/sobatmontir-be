<?php

// 2024_01_01_000004_create_transaction_accounting_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained('shifts');
            $table->foreignUuid('customer_id')->nullable()->constrained('users');
            $table->decimal('total', 15, 2);
            $table->enum('payment_method', ['CASH', 'QRIS'])->nullable();
            $table->enum('status', ['DRAFT', 'PAID'])->default('DRAFT');
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->integer('qty');
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // Inventory Movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions');
            $table->enum('movement_type', ['SALE', 'ADJUSTMENT']);
            $table->integer('qty_change'); // Bisa negatif atau positif
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('adjusted_by')->constrained('users');
            $table->integer('qty_before');
            $table->integer('qty_after');
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->useCurrent();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->enum('method', ['CASH', 'QRIS']);
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at')->useCurrent();
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('type', ['QUEUE', 'SOS', 'SYSTEM']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // Reports
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops');
            $table->date('date');
            $table->decimal('total_sales', 15, 2);
            $table->integer('total_transactions');
            $table->decimal('total_cash', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
