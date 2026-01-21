<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMechanic;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Workshop;
use DB;
use Exception;

class SosService
{
    /**
     * User membuat Request SOS
     */
    public function requestSos($user, array $data)
    {
        $activeRequest = ServiceRequest::where('customer_id', $user->id)
            ->whereIn('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'PROCESSING'])
            ->exists();
        if ($activeRequest) {
            return [
                'data' => null,
                'message' => 'Anda masih memiliki pesanan SOS yang aktif',
                'code' => 400
            ];
        }
        $nearbyWorkshops = Workshop::nearby($data['lat'], $data['lng'], 5)->count();
        if ($nearbyWorkshops == 0) {
            return [
                'data' => null,
                'message' => 'Maaf, tidak ada bengkel mobile yang tersedia di sekitar Anda saat ini',
                'code' => 404
            ];
        }
        $order = ServiceRequest::create([
            'customer_id' => $user->id,
            'problem_type' => $data['problem'],
            'pickup_lat' => $data['lat'],
            'pickup_lng' => $data['lng'],
            'status' => 'BROADCAST',
        ]);
        return [
            'data' => $order,
            'message' => 'Sinyal SOS disebarkan! Mencari mekanik terdekat...',
            'code' => 201
        ];
    }

    /**
     * Mekanik melihat list order di sekitarnya (Polling)
     */
    public function nearby($mechanicUser, array $data)
    {
        if (!$mechanicUser->can('sos.accept')) {
            return [
                'data' => null,
                'message' => 'Unauthorized',
                'code' => 403
            ];
        }
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;
        if (!$lat || !$lng) {
            return [
                'data' => null,
                'message' => 'Latitude dan longitude wajib diisi',
                'code' => 400
            ];
        }
        $radius = 10;
        $haversine = "(6371 * acos(cos(radians($lat)) 
                        * cos(radians(pickup_lat)) 
                        * cos(radians(pickup_lng) - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(pickup_lat))))";
        $orders = ServiceRequest::select('*')
            ->selectRaw("$haversine AS distance")
            ->where('status', 'BROADCAST')
            ->having('distance', '<=', $radius)
            ->with('customer:id,name,phone')
            ->orderBy('distance', 'asc')
            ->get();
        return [
            'data' => $orders,
            'message' => 'Order SOS di sekitar Anda',
            'code' => 200
        ];
    }

    /**
     * Mekanik Menerima Order
     */
    public function accept($mechanicUser, $requestId)
    {
        if (!$mechanicUser->can('sos.accept')) {
            return [
                'data' => null,
                'message' => 'Unauthorized',
                'code' => 403
            ];
        }
        return DB::transaction(function () use ($mechanicUser, $requestId) {
            $request = ServiceRequest::lockForUpdate()->find($requestId);
            if (!$request) {
                return [
                    'data' => null,
                    'message' => 'Order tidak ditemukan',
                    'code' => 404
                ];
            }
            if ($request->status !== 'BROADCAST') {
                return [
                    'data' => null,
                    'message' => 'Order ini sudah diambil oleh mekanik lain atau dibatalkan',
                    'code' => 400
                ];
            }
            $workshopMechanic = $mechanicUser->mechanicProfile;
            if (!$workshopMechanic) {
                return [
                    'data' => null,
                    'message' => 'Akun Anda tidak terdaftar sebagai mekanik',
                    'code' => 400
                ];
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
            return [
                'data' => $request->load('customer'),
                'message' => 'Order diterima! Segera meluncur.',
                'code' => 200
            ];
        });
    }

    /**
     * Update Status
     * (OTW -> Arrived -> Processing -> Done)
     */
    public function updateStatus($mechanicUser, $requestId, $status)
    {
        $request = ServiceRequest::where('id', $requestId)->first();
        if (!$request) {
            return [
                'data' => null,
                'message' => 'Order tidak ditemukan',
                'code' => 404
            ];
        }
        $isMyJob = ServiceRequestMechanic::where('service_request_id', $requestId)
            ->where('mechanic_id', $mechanicUser->id)
            ->exists();
        if (!$isMyJob) {
            return [
                'data' => null,
                'message' => 'Anda tidak memiliki akses ke order ini',
                'code' => 403
            ];
        }
        $request->update(['status' => $status]);
        return [
            'data' => $request,
            'message' => 'Status diperbarui.',
            'code' => 200
        ];
    }

    /**
     * Finalisasi Order SOS -> Jadi Transaksi Keuangan
     */
    public function finalize($mechanicUser, $requestId, array $data)
    {
        if (!$mechanicUser->can('sos.accept')) {
            return [
                'data' => null,
                'message' => 'Unauthorized',
                'code' => 403
            ];
        }
        
        return DB::transaction(function () use ($mechanicUser, $requestId, $data) {
            $order = ServiceRequest::where('id', $requestId)->first();
            
            if (!$order) {
                return [
                    'data' => null,
                    'message' => 'Order tidak ditemukan',
                    'code' => 404
                ];
            }
            
            $isMyJob = ServiceRequestMechanic::where('service_request_id', $requestId)
                ->where('mechanic_id', $mechanicUser->id)
                ->exists();
            
                if (!$isMyJob) {
                return [
                    'data' => null,
                    'message' => 'Anda tidak memiliki akses ke order ini',
                    'code' => 403
                ];
            }
            
            if ($order->status === 'DONE') {
                return [
                    'data' => null,
                    'message' => 'Order ini sudah diselesaikan sebelumnya',
                    'code' => 400
                ];
            }
            
            $workshopId = $order->workshop_id;
            
            $shift = Shift::where('workshop_id', $workshopId)
                ->where('status', Shift::STATUS_OPEN)
                ->latest()
                ->first();
            
                if (!$shift) {
                return [
                    'data' => null,
                    'message' => 'Tidak ada shift kasir yang terbuka di bengkel ini. Mohon hubungi admin bengkel.',
                    'code' => 400
                ];
            }
            
            $cartItems = $data['items'];
            $grandTotal = 0;
            $preparedItems = [];
            
            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
            
                if (!$product)
                    continue;
            
                if (!$product->is_service && $product->stock < $item['qty']) {
                    return [
                        'data' => null,
                        'message' => "Stok '{$product->name}' tidak cukup",
                        'code' => 400
                    ];
                }
                $subtotal = $product->price * $item['qty'];
                $grandTotal += $subtotal;
                $preparedItems[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }
            
            $transaction = Transaction::create([
                'shift_id' => $shift->id,
                'customer_id' => $order->customer_id,
                'total' => $grandTotal,
                'payment_method' => $data['payment_method'],
                'status' => Transaction::STATUS_PAID
            ]);
            
            foreach ($preparedItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);
            
                if (!$item['product']->is_service) {
                    $item['product']->decrement('stock', $item['qty']);
                    StockMovement::create([
                        'product_id' => $item['product']->id,
                        'transaction_id' => $transaction->id,
                        'movement_type' => StockMovement::MOVEMENT_TYPE_SALE,
                        'qty_change' => -$item['qty'],
                        'reason' => 'SOS Order #' . substr($order->id, 0, 8),
                    ]);
                }
            }
            
            Payment::create([
                'transaction_id' => $transaction->id,
                'method' => $data['payment_method'],
                'amount' => $grandTotal,
                'paid_at' => now(),
            ]);

            $shift->increment('total_sales', $grandTotal);
            
            if ($data['payment_method'] === Transaction::PAYMENT_METHOD_CASH) {
                $shift->increment('cash_in', $grandTotal);
            }
            
            $order->update(['status' => 'DONE']);
            
            return [
                'data' => [
                    'sos_order' => $order,
                    'transaction' => $transaction->load('items.product'),
                    'total_bill' => $grandTotal,
                ],
                'message' => 'Order selesai & Transaksi tercatat',
                'code' => 200
            ];
        });
    }
    public function myActiveOrder($user)
    {
        $order = ServiceRequest::where('customer_id', $user->id)
            ->whereIn('status', ['BROADCAST', 'ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'PROCESSING'])
            ->with([
                'mechanic' => function ($q) {
                    $q->select('users.id', 'users.name', 'users.phone');
                }
            ])
            ->latest()
            ->first();
        if (!$order) {
            return [
                'data' => null,
                'message' => 'Tidak ada order aktif',
                'code' => 200
            ];
        }
        return [
            'data' => $order,
            'message' => 'Order aktif ditemukan',
            'code' => 200
        ];
    }
}
