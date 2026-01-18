<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->enum('role', ['CONSUMER', 'MECHANIC', 'OWNER', 'ADMIN']);
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Workshops
        Schema::create('workshops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_open')->default(false);
            $table->boolean('is_mobile_service_enabled')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable(); // Ditambahkan untuk konsistensi model
        });

        // 3. Workshop Mechanics (Pivot + Data)
        Schema::create('workshop_mechanics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignUuid('mechanic_id')->constrained('users')->cascadeOnDelete();
            $table->enum('mechanic_type', ['IN_SHOP', 'MOBILE']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_mechanics');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('users');
    }
};
