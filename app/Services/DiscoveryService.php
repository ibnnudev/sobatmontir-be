<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Workshop;
use App\Models\WorkshopGallery;

class DiscoveryService
{
    /**
     * Cari Bengkel (Filter & Lokasi)
     */
    public function searchWorkshops($filters)
    {
        $lat = $filters['lat'] ?? null;
        $lng = $filters['lng'] ?? null;
        $radius = $filters['radius'] ?? 10;
        $is24Hours = !empty($filters['is_24_hours']);
        $serviceName = $filters['service_name'] ?? null;

        return Workshop::query()
            ->where('is_open', true)

            ->when($lat && $lng, function ($query) use ($lat, $lng, $radius) {
                $query->nearby($lat, $lng, $radius);
            })

            ->with([
                'services' => function ($q) use ($is24Hours, $serviceName) {
                    if ($is24Hours) {
                        $q->where('is_24_hours', true);
                    }

                    if ($serviceName) {
                        $q->where('service_name', 'like', "%{$serviceName}%");
                    }
                },
                'galleries',
            ])

            ->get();
    }


    /**
     * Tambah Review User
     */
    public function addReview($user, $data)
    {
        /**
         * Validasi: User tidak boleh review bengkel sendiri (jika dia owner)
         * Atau validasi user harus pernah transaksi (Opsional)
         */
        return Review::create([
            'workshop_id' => $data['workshop_id'],
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /**
     * Owner Upload Galeri
     */
    public function addGalleryImage($user, $data)
    {
        // Pastikan workshop milik user
        $workshop = Workshop::where('id', $data['workshop_id'])
            ->where('owner_id', $user->id)
            ->firstOrFail();

        return WorkshopGallery::create([
            'workshop_id' => $workshop->id,
            'image_url' => $data['image_url'], // URL dari S3/Cloudinary
            'caption' => $data['caption'] ?? null,
        ]);
    }
}