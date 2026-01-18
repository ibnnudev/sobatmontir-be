<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyOperationSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil Data Master
        $workshop = DB::table('workshops')->first();
        $owner = DB::table('users')->where('role', 'OWNER')->first();
        $mechanic = DB::table('users')->where('role', 'MECHANIC')->first();
        $consumer = DB::table('users')->where('role', 'CONSUMER')->first();

        // Ambil Produk Real
        $oliShell = DB::table('products')->where('name', 'like', '%Shell%')->first();
        $jasaGanti = DB::table('products')->where('name', 'Jasa Ganti Oli')->first();

        // 1. BUKA SHIFT KASIR (Pagi jam 08:00)
        $shiftId = Str::uuid();
        DB::table('shifts')->insert([
            'id' => $shiftId,
            'workshop_id' => $workshop->id,
            'cashier_id' => $owner->id, // Owner jaga kasir
            'opening_cash' => 200000, // Modal awal recehan 200rb
            'total_sales' => 0,
            'status' => 'OPEN',
            'opened_at' => Carbon::now()->subHours(4), // Buka 4 jam lalu
            'created_at' => now(),
        ]);

        // 2. BUKA ANTRIAN HARI INI
        $queueId = Str::uuid();
        DB::table('queues')->insert([
            'id' => $queueId,
            'workshop_id' => $workshop->id,
            'date' => Carbon::today(),
            'traffic_status' => 'SEDANG',
            'created_at' => now(),
        ]);

        // 3. BUKA TIKET ANTRIAN (Customer sedang diservis)
        DB::table('queue_tickets')->insert([
            'id' => Str::uuid(),
            'queue_id' => $queueId,
            'workshop_id' => $workshop->id,
            'customer_id' => $consumer->id,
            'ticket_code' => 'A-001',
            'status' => 'SERVING',
            'estimated_serve_at' => Carbon::now()->addMinutes(15),
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        // 4. TRANSAKSI (Customer beli Oli + Jasa)
        $trxId = Str::uuid();
        $qtyOli = 1;
        $qtyJasa = 1;
        $total = ($oliShell->price * $qtyOli) + ($jasaGanti->price * $qtyJasa);

        DB::table('transactions')->insert([
            'id' => $trxId,
            'shift_id' => $shiftId,
            'customer_id' => $consumer->id,
            'total' => $total,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Detail Item Transaksi
        DB::table('transaction_items')->insert([
            [
                'id' => Str::uuid(),
                'transaction_id' => $trxId,
                'product_id' => $oliShell->id,
                'qty' => $qtyOli,
                'price' => $oliShell->price,
                'subtotal' => $oliShell->price * $qtyOli,
            ],
            [
                'id' => Str::uuid(),
                'transaction_id' => $trxId,
                'product_id' => $jasaGanti->id,
                'qty' => $qtyJasa,
                'price' => $jasaGanti->price,
                'subtotal' => $jasaGanti->price * $qtyJasa,
            ],
        ]);

        // Payment Log
        DB::table('payments')->insert([
            'id' => Str::uuid(),
            'transaction_id' => $trxId,
            'method' => 'QRIS',
            'amount' => $total,
            'paid_at' => now(),
        ]);

        // Kurangi Stok (Stock Movement Log)
        DB::table('stock_movements')->insert([
            'id' => Str::uuid(),
            'product_id' => $oliShell->id,
            'transaction_id' => $trxId,
            'movement_type' => 'SALE',
            'qty_change' => -1,
            'reason' => 'Penjualan via Kasir',
            'created_at' => now(),
        ]);

        // Update Total Sales di Shift
        DB::table('shifts')->where('id', $shiftId)->increment('total_sales', $total);

        // 5. SIMULASI SOS (Mogok di jalan)
        $sosId = Str::uuid();
        DB::table('service_requests')->insert([
            'id' => $sosId,
            'customer_id' => $consumer->id,
            'workshop_id' => $workshop->id,
            'problem_type' => 'Ban Bocor',
            // Lokasi sekitar 1km dari bengkel
            'pickup_lat' => -6.299000,
            'pickup_lng' => 106.800000,
            'status' => 'ON_THE_WAY', // Mekanik sedang jalan
            'created_at' => now(),
        ]);

        DB::table('service_request_mechanics')->insert([
            'id' => Str::uuid(),
            'service_request_id' => $sosId,
            'mechanic_id' => $mechanic->id, // Asep Knalpot
            'accepted_at' => now(),
        ]);

        // Update Lokasi Mekanik (Live Tracking)
        DB::table('mechanic_locations')->insert([
            'id' => Str::uuid(),
            'mechanic_id' => $mechanic->id,
            'latitude' => -6.295000, // Di tengah jalan antara bengkel dan customer
            'longitude' => 106.799500,
            'updated_at' => now(),
        ]);
    }
}
