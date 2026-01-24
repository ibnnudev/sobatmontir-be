<?php

namespace Database\Seeders;

use App\Models\User; // <--- PENTING: Gunakan Model, jangan DB Facade untuk User
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Users Menggunakan ELOQUENT (Agar bisa assignRole)

        // --- OWNER ---
        $owner = User::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'owner@bengkelmaju.com',
            'role' => 'OWNER', // Kolom database biasa
            'password' => 'password', // Mutator 'hashed' di model akan otomatis mengenkripsi ini
            'is_active' => true,
        ]);
        // Tetapkan Role Spatie (Ini yang dicek oleh Gate/Policy)
        $owner->assignRole('owner');

        // --- MECHANIC 1 (IN SHOP) ---
        $mechanic1 = User::create([
            'name' => 'Asep Knalpot',
            'phone' => '081298765432',
            'email' => 'asep@bengkelmaju.com',
            'role' => 'MECHANIC',
            'password' => 'password',
            'is_active' => true,
        ]);
        $mechanic1->assignRole('mechanic_in_shop');

        // --- MECHANIC 2 (MOBILE) ---
        $mechanic2 = User::create([
            'name' => 'Udin Karburator',
            'phone' => '081355556666',
            'email' => 'udin@bengkelmaju.com',
            'role' => 'MECHANIC',
            'password' => 'password',
            'is_active' => true,
        ]);
        $mechanic2->assignRole('mechanic_mobile');

        // --- CONSUMER ---
        $consumer = User::create([
            'name' => 'Siti Aminah',
            'phone' => '081122334455',
            'email' => 'siti.consumer@gmail.com',
            'role' => 'CONSUMER',   
            'password' => 'password',
            'is_active' => true,
        ]);
        $consumer->assignRole('consumer');

        // ==========================================
        // SISA KODE DI BAWAH BOLEH PAKAI DB::table
        // KARENA TIDAK BUTUH LOGIC ELOQUENT/SPATIE
        // ==========================================

        // 2. Buat Workshop
        $workshopId = Str::uuid();
        DB::table('workshops')->insert([
            'id' => $workshopId,
            'owner_id' => $owner->id, // Ambil ID dari object User yang baru dibuat
            'name' => 'Bengkel Maju Jaya Motor',
            'address' => 'Jl. RS. Fatmawati Raya No. 15, Cilandak, Jakarta Selatan',
            'latitude' => -6.292321,
            'longitude' => 106.798991,
            'is_open' => true,
            'is_mobile_service_enabled' => true,
            'created_at' => now(),
        ]);

        // 3. Assign Mechanic to Workshop
        DB::table('workshop_mechanics')->insert([
            [
                'id' => Str::uuid(),
                'workshop_id' => $workshopId,
                'mechanic_id' => $mechanic1->id,
                'mechanic_type' => 'IN_SHOP',
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'workshop_id' => $workshopId,
                'mechanic_id' => $mechanic2->id,
                'mechanic_type' => 'MOBILE',
                'is_active' => true,
            ],
        ]);

        // 4. Workshop Services
        DB::table('workshop_services')->insert([
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-LGT', 'service_name' => 'Service Ringan / Tune Up', 'is_24_hours' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-CVT', 'service_name' => 'Service CVT Matic', 'is_24_hours' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-BAN', 'service_name' => 'Tambal Ban Tubeless', 'is_24_hours' => true],
        ]);

        // 5. Products
        DB::table('products')->insert([
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli Shell Advance AX7 10W-40 (0.8L)', 'price' => 65000, 'stock' => 24, 'min_stock' => 5, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli Yamalube Matic (0.8L)', 'price' => 55000, 'stock' => 30, 'min_stock' => 5, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli MPX2 Honda (0.8L)', 'price' => 52000, 'stock' => 40, 'min_stock' => 10, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Kampas Rem Depan Honda Beat/Vario', 'price' => 85000, 'stock' => 10, 'min_stock' => 2, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Busi NGK CPR9EA', 'price' => 25000, 'stock' => 50, 'min_stock' => 10, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Jasa Ganti Oli', 'price' => 10000, 'stock' => 9999, 'min_stock' => 0, 'is_service' => true],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Jasa Pasang Kampas Rem', 'price' => 20000, 'stock' => 9999, 'min_stock' => 0, 'is_service' => true],
        ]);
    }
}
