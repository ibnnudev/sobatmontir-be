<?php

namespace App\Services;

use App\Models\MechanicLocation;
use App\Models\ServiceRequest;
use Exception;

class TrackingService
{
    /**
     * Update Lokasi Mekanik
     */
    public function updateMechanicLocation($user, $lat, $lng)
    {
        // Pastiin mekanik hanya punya 1 record lokasi terakhir
        return MechanicLocation::updateOrCreate(
            ['mechanic_id' => $user->id],
            [
                'latitude' => $lat,
                'longitude' => $lng,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Get Lokasi Mekanik & Hitung ETA untuk Customer
     */
    public function getTrackingInfo($customerUser, $orderId)
    {
        // Validasi Order
        $order = ServiceRequest::where('id', $orderId)
            ->where('customer_id', $customerUser->id)
            ->whereIn('status', ['ACCEPTED', 'ON_THE_WAY'])
            ->first();

        // Ambil Mekanik yang menangani (ACCEPTED, ON_THE_WAY)
        $mechanic = $order->mechanic;

        if (!$mechanic)
            throw new Exception('Mekanik belum terassign.');

        // Ambil Lokasi Terkini
        $location = MechanicLocation::where('mechanic_id', $mechanic->id)->first();
        if (!$location) {
            return [
                'status' => 'WAITING_SIGNAL',
                'message' => 'Mekanik belum mengirim sinyal lokasi',
                'mechanic' => $mechanic->name
            ];
        }

        /**
         * Hitung Jarak & ETA
         * Destinasi: Lokasi Customer (order->pickup_lat)
         * Asal: Lokasi Mekanik (location->latitude)
         */
        $distanceKm = $this->calculateDistance(
            $location->latitude,
            $location->longitude,
            $order->pickup_lat,
            $order->pickup_lng
        );

        // Asumsi Kecepatan Rata-rata Mekanik: 40 KM/Jam
        $etaMinutes = ceil(($distanceKm / 40) * 60);

        return [
            'status' => 'TRACKING',
            'mechanic' => [
                'name' => $mechanic->name,
                'phone' => $mechanic->phone,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'last_update' => $location->updated_at
            ],
            'destination' => [
                'lat' => $order->pickup_lat,
                'lng' => $order->pickup_lng
            ],
            'estimation' => [
                'distance_km' => round($distanceKm, 2),
                'eta_minutes' => $etaMinutes < 1 ? 1 : $etaMinutes
            ]
        ];
    }

    /**
     * Hitung Jarak (KM)
     * Haversine
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius bumi dalam kilometer

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}