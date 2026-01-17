<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Users (Owner, Mechanic, Consumer)
        $ownerId = Str::uuid();
        $mechanicId1 = Str::uuid();
        $mechanicId2 = Str::uuid();
        $consumerId = Str::uuid();

        $users = [
            [
                'id' => $ownerId,
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'owner@bengkelmaju.com',
                'role' => 'OWNER',
                'password' => Hash::make('password'), // Pastikan model User punya field password (default laravel)
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => $mechanicId1,
                'name' => 'Asep Knalpot',
                'phone' => '081298765432',
                'email' => 'asep@bengkelmaju.com',
                'role' => 'MECHANIC',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => $mechanicId2,
                'name' => 'Udin Karburator',
                'phone' => '081355556666',
                'email' => 'udin@bengkelmaju.com',
                'role' => 'MECHANIC',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => $consumerId,
                'name' => 'Siti Aminah',
                'phone' => '081122334455',
                'email' => 'siti.consumer@gmail.com',
                'role' => 'CONSUMER',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
            ]
        ];
        DB::table('users')->insert($users);

        // 2. Buat Workshop (Lokasi: Jakarta Selatan)
        $workshopId = Str::uuid();
        DB::table('workshops')->insert([
            'id' => $workshopId,
            'owner_id' => $ownerId,
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
                'mechanic_id' => $mechanicId1,
                'mechanic_type' => 'IN_SHOP',
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'workshop_id' => $workshopId,
                'mechanic_id' => $mechanicId2,
                'mechanic_type' => 'MOBILE',
                'is_active' => true,
            ]
        ]);

        // 4. Workshop Services (Daftar Layanan)
        DB::table('workshop_services')->insert([
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-LGT', 'service_name' => 'Service Ringan / Tune Up', 'is_24_hours' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-CVT', 'service_name' => 'Service CVT Matic', 'is_24_hours' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'service_code' => 'SVC-BAN', 'service_name' => 'Tambal Ban Tubeless', 'is_24_hours' => true],
        ]);

        // 5. Products (Inventory Nyata)
        DB::table('products')->insert([
            // Oli
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli Shell Advance AX7 10W-40 (0.8L)', 'price' => 65000, 'stock' => 24, 'min_stock' => 5, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli Yamalube Matic (0.8L)', 'price' => 55000, 'stock' => 30, 'min_stock' => 5, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Oli MPX2 Honda (0.8L)', 'price' => 52000, 'stock' => 40, 'min_stock' => 10, 'is_service' => false],
            // Sparepart Fast Moving
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Kampas Rem Depan Honda Beat/Vario', 'price' => 85000, 'stock' => 10, 'min_stock' => 2, 'is_service' => false],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Busi NGK CPR9EA', 'price' => 25000, 'stock' => 50, 'min_stock' => 10, 'is_service' => false],
            // Jasa (Product tipe Service)
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Jasa Ganti Oli', 'price' => 10000, 'stock' => 9999, 'min_stock' => 0, 'is_service' => true],
            ['id' => Str::uuid(), 'workshop_id' => $workshopId, 'name' => 'Jasa Pasang Kampas Rem', 'price' => 20000, 'stock' => 9999, 'min_stock' => 0, 'is_service' => true],
        ]);
    }
}