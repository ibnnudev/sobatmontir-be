<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\SosService;
use Gate;
use Illuminate\Http\Request;

class SosController extends Controller
{
    protected $sosService;

    public function __construct(SosService $sosService)
    {
        $this->sosService = $sosService;
    }

    // [CUSTOMER] Panggil Mekanik
    public function requestSos(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'problem' => 'required|string|',
        ]);

        // Pastikan user punya role consumer (atau allowed)
        Gate::authorize('sos.create');

        try {
            $data = $this->sosService->createRequest($request->user(), $request->all());
            return response()->json([
                'message' => 'Sinyal SOS disebarkan! Mencari mekanik terdekat...',
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal membuat permintaan SOS: ' . $th->getMessage(),
            ], 500);
        }
    }

    // [MECANIC] Cek Order di Sekitar (Polling)
    public function nearBy(Request $request)
    {
        // Mekanik kirim lokasi dia saat ini
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // Cek Permission
        if (!$request->user()->can('sos.accept')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $orders = $this->sosService->getNearbyRequests($request->user(), $request->lat, $request->lng);
        return response()->json([
            'data' => $orders,
        ]);
    }

    // [MECHANIC] Terima Order
    public function accept(Request $request, $id)
    {
        if (!$request->user()->can('sos.accept')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->sosService->acceptRequest($request->user(), $id);
            return response()->json(['message' => 'Order diterima! Segera meluncur.', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // [MECHANIC] Update Status (OTW, Sampai, dll)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:ON_THE_WAY,ARRIVED,PROCESSING,DONE,CANCELLED'
        ]);

        try {
            $data = $this->sosService->updateStatus($request->user(), $id, $request->status);
            return response()->json(['message' => 'Status diperbarui.', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // [CUSTOMER] Cek Status Order saya (Polling)
    public function myActiveOrder(Request $request)
    {
        $order = ServiceRequest::where('customer_id', $request->user()->id)
            ->whereIn('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'PROCESSING'])
            ->with([
                'mechanic' => function ($q) {
                    $q->select('users.id', 'users.name', 'users.phone'); // Tampilkan info mekanik yang accepted
                }
            ])
            ->latest()
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Tidak ada order aktif', 'data' => null]);
        }

        return response()->json(['data' => $order]);
    }
}
