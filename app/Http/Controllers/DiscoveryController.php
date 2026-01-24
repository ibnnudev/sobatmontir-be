<?php

namespace App\Http\Controllers;
use App\Http\Requests\DiscoveryReviewRequest;
use App\Http\Requests\DiscoveryReviewUploadRequest;
use App\Http\Responses\ApiResponse;
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
        return ApiResponse::success($results);
    }

    // [PUBLIC] Detail Bengkel + Review
    public function show($id)
    {
        $workshop = $this->discoveryService->getWorkshopDetail($id);
        return ApiResponse::success($workshop);
    }

    // [CUSTOMER] Kasih Review
    public function storeReview(DiscoveryReviewRequest $request)
    {
        $review = $this->discoveryService->addReview($request->user(), $request->only([
            'workshop_id',
            'rating',
            'comment',
        ]));
        return ApiResponse::success($review, 'Review berhasil dikirim', 201);
    }

    // [OWNER] Upload Foto Galeri
    public function uploadGallery(DiscoveryReviewUploadRequest $request)
    {
        if (!$request->user()->hasRole('owner')) {
            return ApiResponse::error('Unauthorized', 403);
        }
        try {
            $gallery = $this->discoveryService->addGalleryImage($request->user(), $request->only([
                'workshop_id',
                'image_url',
                'caption',
            ]));
            return ApiResponse::success($gallery, 'Foto berhasil diupload', 201);
        } catch (\Throwable $th) {
            return ApiResponse::error('Gagal upload foto: ' . $th->getMessage(), 400);
        }
    }
}
