<?php

namespace App\Repositories;

use App\Models\MechanicLocation;
use App\Models\ServiceRequest;

class TrackingRepository
{
    public function updateMechanicLocation($mechanicId, $lat, $lng)
    {
        return MechanicLocation::updateOrCreate(
            ['mechanic_id' => $mechanicId],
            [
                'latitude' => $lat,
                'longitude' => $lng,
                'updated_at' => now(),
            ]
        );
    }

    public function getMechanicLocation($mechanicId)
    {
        return MechanicLocation::where('mechanic_id', $mechanicId)->first();
    }

    public function getOrderForTracking($customerId, $orderId)
    {
        return ServiceRequest::where('id', $orderId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['ACCEPTED', 'ON_THE_WAY'])
            ->first();
    }
}
