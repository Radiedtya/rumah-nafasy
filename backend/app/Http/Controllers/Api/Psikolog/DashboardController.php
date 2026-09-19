<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $psikolog = $request->user();
        $profile = $psikolog->psikologProfile;
        $today = now()->format('Y-m-d');

        // Today's bookings (whereDate — kolom date tersimpan dengan komponen waktu di SQLite)
        $todayBookings = Booking::where('psikolog_id', $psikolog->id)
            ->whereDate('booking_date', $today)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->with(['pasien', 'order.category', 'order.duration'])
            ->orderBy('start_time')
            ->get();

        // Upcoming bookings (future, not today)
        $upcomingCount = Booking::where('psikolog_id', $psikolog->id)
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>', $today)
            ->count();

        // Total completed consultations
        $totalCompleted = Consultation::whereHas('booking', function ($q) use ($psikolog) {
            $q->where('psikolog_id', $psikolog->id);
        })->where('status', 'completed')->count();

        // Monthly income
        $monthlyIncome = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->sum('calculated_price');

        // Total income
        $totalIncome = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->sum('calculated_price');

        return $this->successResponse([
            'today_bookings' => BookingResource::collection($todayBookings),
            'today_bookings_count' => $todayBookings->count(),
            'upcoming_bookings_count' => $upcomingCount,
            'total_completed_consultations' => $totalCompleted,
            'monthly_income' => (float) $monthlyIncome,
            'total_income' => (float) $totalIncome,
            'rating_avg' => $profile ? (float) $profile->rating_avg : 0,
            'total_reviews' => $profile ? $profile->total_reviews : 0,
            'is_available' => $profile ? $profile->is_available : false,
            'specialization' => $profile?->specialization?->name,
        ], 'Dashboard psikolog');
    }
}