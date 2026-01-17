<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Definisi Permission (Berdasarkan)
        $permissions = [
            // Cari Bengkel
            'workshop.search',

            // Ambil Antrian (Konsumen & Walk-in Mechanic)
            'queue.create',

            // Panggil Nomor
            'queue.call',

            // Terima Order SOS
            'sos.accept',

            // Input Transaksi (POS)
            'transaction.create',

            // Edit Harga
            'price.edit',

            // Input Stok Gudang
            'inventory.manage',

            // Lihat Laporan Keuangan
            'finance.view_report',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. Definisi Roles & Assignment (Berdasarkan Kolom di Gambar)

        // --- ROLE: CONSUMER ---
        $consumer = Role::create(['name' => 'consumer']);
        $consumer->givePermissionTo([
            'workshop.search',  // ✅ Cari Bengkel
            'queue.create',     // ✅ Ambil Antrian
        ]);

        // --- ROLE: MECHANIC (IN-SHOP) ---
        // Mekanik yang stay di bengkel
        $mechanicShop = Role::create(['name' => 'mechanic_in_shop']);
        $mechanicShop->givePermissionTo([
            'queue.create',       // ✅ Ambil Antrian (Untuk Walk-in customer)
            'queue.call',         // ✅ Panggil Nomor
            'transaction.create', // ✅ Input Transaksi
        ]);

        // --- ROLE: MECHANIC (MOBILE) ---
        // Mekanik khusus keliling/SOS
        $mechanicMobile = Role::create(['name' => 'mechanic_mobile']);
        $mechanicMobile->givePermissionTo([
            'sos.accept',         // ✅ Terima Order SOS
            'transaction.create', // ✅ Input Transaksi
        ]);

        // --- ROLE: OWNER ---
        $owner = Role::create(['name' => 'owner']);
        $owner->givePermissionTo([
            'queue.call',          // ✅ Panggil Nomor
            'sos.accept',          // ✅ Terima Order SOS
            'transaction.create',  // ✅ Input Transaksi
            'price.edit',          // ✅ Edit Harga
            'inventory.manage',    // ✅ Input Stok
            'finance.view_report', // ✅ Laporan Keuangan
        ]);

        // --- SUPER ADMIN (Opsional, akses semua) ---
        $admin = Role::create(['name' => 'super_admin']);
        $admin->givePermissionTo(Permission::all());
    }
}