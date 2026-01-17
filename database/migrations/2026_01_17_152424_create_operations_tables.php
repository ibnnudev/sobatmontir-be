<?php

// 2024_01_01_000003_create_operations_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // --- QUEUE SYSTEM ---
        Schema::create('queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->date('date');
            $table->enum('traffic_status', ['SEPI', 'SEDANG', 'RAME'])->default('SEPI');
            $table->timestamps();
        });

        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->foreignUuid('workshop_id')->constrained('workshops');
            $table->foreignUuid('customer_id')->constrained('users');
            $table->string('ticket_code');
            $table->timestamp('estimated_serve_at')->nullable();
            $table->enum('status', ['WAITING', 'SERVING', 'DONE', 'CANCELLED'])->default('WAITING');
            $table->text('qr_code')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('queue_display_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops');
            $table->string('ticket_code');
            $table->enum('action', ['CALLED', 'SKIPPED', 'DONE']);
            $table->timestamp('created_at')->useCurrent();
        });

        // --- SOS / ON DEMAND ---
        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users');
            $table->foreignUuid('workshop_id')->nullable()->constrained('workshops');
            $table->string('problem_type')->nullable();
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->enum('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'DONE', 'CANCELLED'])->default('BROADCAST');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('service_request_mechanics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignUuid('mechanic_id')->constrained('users');
            $table->timestamp('accepted_at')->useCurrent();
        });

        Schema::create('mechanic_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mechanic_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('updated_at')->useCurrent();
        });

        // --- POS SHIFTS ---
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops');
            $table->foreignUuid('cashier_id')->constrained('users');
            $table->decimal('opening_cash', 15, 2);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('cash_in', 15, 2)->default(0);
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('mechanic_locations');
        Schema::dropIfExists('service_request_mechanics');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('queue_display_logs');
        Schema::dropIfExists('queue_tickets');
        Schema::dropIfExists('queues');
    }
};