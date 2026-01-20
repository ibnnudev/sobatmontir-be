<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMechanic;
use App\Models\Workshop;
use DB;
use Exception;

class SosService
{
    /**
     * User membuat Request SOS 
     */
    public function createRequest($user, array $data)
    {
        // Validasi
        $activeRequest = ServiceRequest::where('customer_id', $user->id)
            ->whereIn('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'PROCESSING'])
            ->exists();

        if ($activeRequest) {
            throw new Exception('Anda masih memiliki pesanan SOS yang aktif');
        }

        /**
         * Cek ketersediaan Bengkel Mobile di radius 5km
         * Cek apakah ada bengkel, tapi requestnya tetap 'BROADCAST'
         * ke sistem, nanti bengkel yang akan 'Polling' request tersebut
         */
        $nearbyWorkshops = Workshop::nearby($data['lat'], $data['lng'], 5)->count();

        if ($nearbyWorkshops == 0) {
            throw new Exception('Maaf, tidak ada bengkel mobile yang tersedia di sekitar Anda saat ini');
        }

        // Create Request
        return ServiceRequest::create([
            'customer_id' => $user->id,
            'problem_type' => $data['problem'],
            'pickup_lat' => $data['lat'],
            'pickup_lng' => $data['lng'],
            'status' => 'BROADCAST',
        ]);
    }

    /**
     * Mekanik melihat list order di sekitarnya (Polling)
     */
    public function getNearbyRequests($mechanicUser, $lat, $lng)
    {
        /**
         * Asumsi: Mekanik terikat dengan workshop
         * Sebenarnya mekanik mobile bisa jalan sendiri, tapi di Fase 1 relation di-set via WorkshopMechanic
         * 
         * Logic: Tampilkan Request status 'BROADCAST' yang ada di radius 10km dari lokasi mekanik
         * Menggunakan Raw Query untuk perhitungan Haversine di ServiceRequest (Agak berat tapi akurat)
         */
        $radius = 10; // km
        $haversine = "(6371 * acos(cos(radians($lat)) 
                        * cos(radians(pickup_lat)) 
                        * cos(radians(pickup_lng) - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(pickup_lat))))";
        return ServiceRequest::select('*')
            ->selectRaw("$haversine AS distance")
            ->where('status', 'BROADCAST')
            ->having('distance', '<=', $radius)
            ->with('customer:id,name,phone') // Eager load data customer
            ->orderBy('distance', 'asc')
            ->get();
    }

    /**
     * Mekanik Menerima Order
     */
    public function acceptRequest($mechanicUser, $requestId)
    {
        return DB::transaction(function () use ($mechanicUser, $requestId) {
            // Lock Row (prevent Race Condition)
            $request = ServiceRequest::lockForUpdate()->find($requestId);

            if (!$request)
                throw new Exception('Order tidak ditemukan');
            if ($request->status !== 'BROADCAST')
                throw new Exception('Order ini sudah diambil oleh mekanik lain atau dibatalkan');

            // Ambil Workshop ID dari Mekanik
            $workshopMechanic = $mechanicUser->mechanicProfile;
            if (!$workshopMechanic)
                throw new Exception('Akun Anda tidak terdaftar sebagai mekanik');

            // Update Status Request
            $request->update([
                'status' => 'ACCEPTED',
                'workshop_id' => $workshopMechanic->workshop_id,
            ]);

            // Catat Siapa Mekanik yang ambil
            ServiceRequestMechanic::create([
                'service_request_id' => $request->id,
                'mechanic_id' => $mechanicUser->id,
                'accepted_at' => now(),
            ]);

            return $request->load('customer');
        });
    }

    /**
     * Update Status
     * (OTW -> Arrived -> Processing -> Done)
     */
    public function updateStatus($mechanicUser, $requestId, $status)
    {
        $request = ServiceRequest::where('id', $requestId)->firstOrFail();

        // Validasi kepemilikan
        $isMyJob = ServiceRequestMechanic::where('service_request_id', $requestId)
            ->where('mechanic_id', $mechanicUser->id)
            ->exists();

        if (!$isMyJob) {
            throw new Exception('Anda tidak memiliki akses ke order ini');
        }

        // Validasi Alur Status
        // (ACCEPTED -> ON_THE_WAY -> ARRIVED -> PROCESSING -> DONE)
        // Logika sederhana: langsung update aja
        $request->update(['status' => $status]);
        return $request;
    }
}