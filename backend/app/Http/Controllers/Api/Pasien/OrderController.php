<?php

namespace App\Http\Controllers\Api\Pasien;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Pasien\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\ClientCategory;
use App\Models\DurationOption;
use App\Models\Order;
use App\Models\User;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use App\Services\PricingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private PricingService $pricingService
    ) {}

    /**
     * List orders for authenticated pasien.
     */
    public function index(Request $request)
    {
        $query = Order::where('pasien_id', $request->user()->id)
            ->with(['psikolog.psikologProfile.specialization', 'category', 'duration', 'payment', 'booking'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse(
            $orders,
            'Daftar pesanan Anda',
            OrderResource::class
        );
    }

    /**
     * Create new order (e-commerce style: pilih psikolog + kategori + durasi).
     */
    public function store(CreateOrderRequest $request)
    {
        $psikolog = User::role('psikolog')
            ->whereHas('psikologProfile', function ($q) {
                $q->where('status', 'verified')->where('is_available', true);
            })
            ->with('psikologProfile')
            ->find($request->psikolog_id);

        if (!$psikolog) {
            return $this->errorResponse('Psikolog tidak tersedia', 422);
        }

        $category = ClientCategory::where('is_active', true)->find($request->category_id);
        $duration = DurationOption::where('is_active', true)->find($request->duration_id);

        // Calculate price
        $price = $this->pricingService->calculatePrice(
            $category,
            $duration,
            $psikolog->psikologProfile
        );

        // Create order
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'pasien_id' => $request->user()->id,
            'psikolog_id' => $psikolog->id,
            'category_id' => $category->id,
            'duration_id' => $duration->id,
            'calculated_price' => $price,
            'consultation_type' => $request->consultation_type,
            'status' => 'pending_payment',
            'expires_at' => now()->addDay(), // 24 jam untuk bayar
        ]);

        $order->load(['psikolog.psikologProfile.specialization', 'category', 'duration']);

        app(FonnteService::class)->notifyUser(
            $order->pasien,
            WhatsAppMessages::orderCreated($order)
        );
        
        return $this->successResponse(
            new OrderResource($order),
            'Pesanan dibuat. Silakan lakukan pembayaran dalam 24 jam.',
            201
        );
    }

    /**
     * Show order detail.
     */
    public function show(Request $request, Order $order)
    {
        // Authorization: only own order
        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke pesanan ini', 403);
        }

        $order->load(['psikolog.psikologProfile.specialization', 'category', 'duration', 'payment', 'booking']);

        return $this->successResponse(
            new OrderResource($order),
            'Detail pesanan'
        );
    }

    /**
     * Cancel order (only if pending_payment).
     */
    public function cancel(Request $request, Order $order)
    {
        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$order->isPendingPayment()) {
            return $this->errorResponse('Pesanan tidak dapat dibatalkan', 422);
        }

        $order->update(['status' => 'cancelled']);

        return $this->successResponse(
            new OrderResource($order->fresh()->load(['psikolog.psikologProfile', 'category', 'duration'])),
            'Pesanan dibatalkan'
        );
    }
}