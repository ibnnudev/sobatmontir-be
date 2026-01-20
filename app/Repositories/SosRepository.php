<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMechanic;

class SosRepository
{
    public function create(array $data)
    {
        return ServiceRequest::create($data);
    }

    public function findActiveByCustomer($customerId)
    {
        return ServiceRequest::where('customer_id', $customerId)
            ->whereIn('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'PROCESSING'])
            ->latest()
            ->first();
    }

    public function getNearbyBroadcast($lat, $lng, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($lat)) 
                        * cos(radians(pickup_lat)) 
                        * cos(radians(pickup_lng) - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(pickup_lat))))";

        return ServiceRequest::select('*')
            ->selectRaw("$haversine AS distance")
            ->where('status', 'BROADCAST')
            ->having('distance', '<=', $radius)
            ->with('customer:id,name,phone')
            ->orderBy('distance', 'asc')
            ->get();
    }

    public function acceptRequest($mechanicUser, $requestId)
    {
        return \DB::transaction(function () use ($mechanicUser, $requestId) {
            $request = ServiceRequest::lockForUpdate()->find($requestId);
            if (! $request) {
                throw new \Exception('Order tidak ditemukan');
            }
            if ($request->status !== 'BROADCAST') {
                throw new \Exception('Order ini sudah diambil oleh mekanik lain atau dibatalkan');
            }
            $workshopMechanic = $mechanicUser->mechanicProfile;
            if (! $workshopMechanic) {
                throw new \Exception('Akun Anda tidak terdaftar sebagai mekanik');
            }
            $request->update([
                'status' => 'ACCEPTED',
                'workshop_id' => $workshopMechanic->workshop_id,
            ]);
            ServiceRequestMechanic::create([
                'service_request_id' => $request->id,
                'mechanic_id' => $mechanicUser->id,
                'accepted_at' => now(),
            ]);

            return $request->load('customer');
        });
    }

    public function updateStatus($mechanicUser, $requestId, $status)
    {
        $request = ServiceRequest::where('id', $requestId)->firstOrFail();
        $isMyJob = ServiceRequestMechanic::where('service_request_id', $requestId)
            ->where('mechanic_id', $mechanicUser->id)
            ->exists();
        if (! $isMyJob) {
            throw new \Exception('Anda tidak memiliki akses ke order ini');
        }
        $request->update(['status' => $status]);

        return $request;
    }
}
