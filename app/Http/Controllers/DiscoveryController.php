<?php

namespace App\Http\Controllers;
use App\Http\Requests\DiscoveryReviewRequest;
use App\Http\Requests\DiscoveryReviewUploadRequest;
use App\Models\Workshop;
use App\Services\DiscoveryService;
use Illuminate\Http\Request;


class DiscoveryController extends Controller
{
    protected $discoveryService;

    public function __construct(DiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    // [PUBLIC] Cari Bengkel
    public function search(Request $request)
    {
        $results = $this->discoveryService->searchWorkshops($request->only([
            'lat',
            'lng',
            'radius',
            'is_24_hours',
            'service_name',
        ]));

        return response()->json([
            'data' => $results,
        ]);
    }

    // [PUBLIC] Detail Bengkel + Review
    public function show($id)
    {
        $workshop = Workshop::with([
            'services',
            'galleries',
            'reviews.user:id,name',
        ])->findOrFail($id);

        return response()->json([
            'data' => $workshop,
        ]);
    }

    // [CUSTOMER] Kasih Review
    public function storeReview(DiscoveryReviewRequest $request)
    {
        $review = $this->discoveryService->addReview($request->user(), $request->only([
            'workshop_id',
            'rating',
            'comment',
        ]));

        return response()->json([
            'message' => 'Review berhasil dikirim',
            'data' => $review,
        ], 201);
    }

    // [OWNER] Upload Foto Galeri
    public function uploadGallery(DiscoveryReviewUploadRequest $request)
    {
        // Permission Check (Owner)
        if (!$request->user()->hasRole('owner')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $gallery = $this->discoveryService->addGalleryImage($request->user(), $request->only([
                'workshop_id',
                'image_url',
                'caption',
            ]));

            return response()->json([
                'message' => 'Foto berhasil ditambahkan',
                'data' => $gallery,
            ], 201);
        } catch (\Exception $th) {
            return response()->json(['message' => 'Gagal upload: Workshop tidak ditemukan atau bukan milik Anda'], 400);
        }
    }
}
