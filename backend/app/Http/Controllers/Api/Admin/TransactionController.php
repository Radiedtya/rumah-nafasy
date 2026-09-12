<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['pasien', 'psikolog.psikologProfile', 'category', 'duration', 'payment', 'booking']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('pasien', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('psikolog', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        $orders = $query->latest()->paginate(min($request->get('per_page', 15), 50));

        return $this->paginateResponse($orders, 'Daftar transaksi', OrderResource::class);
    }

    public function show(Order $order)
    {
        $order->load(['pasien', 'psikolog.psikologProfile', 'category', 'duration', 'payment', 'booking', 'refund']);

        return $this->successResponse(new OrderResource($order), 'Detail transaksi');
    }
}