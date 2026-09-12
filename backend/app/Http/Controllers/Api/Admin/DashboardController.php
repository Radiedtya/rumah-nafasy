<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\Order;
use App\Models\PsikologProfile;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalPasien = User::role('pasien')->count();
        $totalPsikolog = User::role('psikolog')->count();
        $totalPsikologVerified = PsikologProfile::where('status', 'verified')->count();
        $totalPsikologPending = PsikologProfile::where('status', 'pending')->count();

        $totalOrders = Order::count();
        $totalCompletedOrders = Order::where('status', 'completed')->count();
        $totalCancelledOrders = Order::where('status', 'cancelled')->count();

        $totalRevenue = Order::where('status', 'completed')->sum('calculated_price');
        $monthlyRevenue = Order::where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->sum('calculated_price');

        $totalRefunds = Refund::where('status', 'completed')->sum('amount');
        $pendingRefunds = Refund::where('status', 'pending')->count();

        $totalConsultations = Consultation::where('status', 'completed')->count();
        $activeBookings = Booking::whereIn('status', ['confirmed', 'in_progress'])->count();

        $recentOrders = Order::with(['pasien', 'psikolog.psikologProfile', 'category', 'duration'])
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::with('roles')->latest()->limit(10)->get();

        return $this->successResponse([
            'users' => [
                'total_pasien' => $totalPasien,
                'total_psikolog' => $totalPsikolog,
                'verified_psikolog' => $totalPsikologVerified,
                'pending_psikolog' => $totalPsikologPending,
            ],
            'orders' => [
                'total' => $totalOrders,
                'completed' => $totalCompletedOrders,
                'cancelled' => $totalCancelledOrders,
            ],
            'revenue' => [
                'total' => (float) $totalRevenue,
                'monthly' => (float) $monthlyRevenue,
                'total_refunds' => (float) $totalRefunds,
                'net_revenue' => (float) ($totalRevenue - $totalRefunds),
            ],
            'consultations' => [
                'total_completed' => $totalConsultations,
                'active_bookings' => $activeBookings,
            ],
            'pending' => [
                'psikolog_verifications' => $totalPsikologPending,
                'refunds' => $pendingRefunds,
            ],
            'recent_orders' => OrderResource::collection($recentOrders),
            'recent_users' => UserResource::collection($recentUsers),
        ], 'Dashboard admin');
    }
}