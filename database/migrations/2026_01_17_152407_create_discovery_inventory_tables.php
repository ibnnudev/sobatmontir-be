<?php

// 2024_01_01_000002_create_discovery_inventory_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workshop Services
        Schema::create('workshop_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->string('service_code')->nullable();
            $table->string('service_name')->nullable();
            $table->boolean('is_24_hours')->default(false);
            $table->timestamps();
        });

        // Workshop Gallery
        Schema::create('workshop_galleries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->text('image_url');
            $table->text('caption')->nullable();
            $table->timestamps();
        });

        // Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Products (Inventory Master)
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('is_service')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('workshop_galleries');
        Schema::dropIfExists('workshop_services');
    }
};
