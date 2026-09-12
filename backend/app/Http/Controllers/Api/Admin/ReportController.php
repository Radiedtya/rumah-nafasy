<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RescheduleLog;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function transactions(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $endDateTime = $endDate . ' 23:59:59';

        $totalRevenue = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDateTime])
            ->sum('calculated_price');

        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDateTime])->count();
        $completedOrders = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDateTime])->count();
        $cancelledOrders = Order::where('status', 'cancelled')
            ->whereBetween('updated_at', [$startDate, $endDateTime])->count();

        $totalRefunds = Refund::where('status', 'completed')
            ->whereBetween('processed_at', [$startDate, $endDateTime])
            ->sum('amount');

        // Daily breakdown
        $dailyStats = Order::selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, SUM(CASE WHEN status = "completed" THEN calculated_price ELSE 0 END) as revenue')
            ->whereBetween('created_at', [$startDate, $endDateTime])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // By category
        $byCategory = Order::selectRaw('client_categories.name, COUNT(*) as total, SUM(orders.calculated_price) as revenue')
            ->join('client_categories', 'orders.category_id', '=', 'client_categories.id')
            ->whereBetween('orders.created_at', [$startDate, $endDateTime])
            ->where('orders.status', 'completed')
            ->groupBy('client_categories.name')
            ->get();

        return $this->successResponse([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_refunds' => (float) $totalRefunds,
                'net_revenue' => (float) ($totalRevenue - $totalRefunds),
                'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
            ],
            'daily_stats' => $dailyStats,
            'by_category' => $byCategory,
        ], 'Laporan transaksi');
    }

    public function psikolog(Request $request)
    {
        $psikologs = User::role('psikolog')
            ->with(['psikologProfile.specialization'])
            ->get();

        $stats = $psikologs->map(function ($psikolog) {
            $income = Order::where('psikolog_id', $psikolog->id)
                ->where('status', 'completed')
                ->sum('calculated_price');

            $totalOrders = Order::where('psikolog_id', $psikolog->id)->count();
            $completedOrders = Order::where('psikolog_id', $psikolog->id)
                ->where('status', 'completed')->count();

            return [
                'id' => $psikolog->id,
                'name' => $psikolog->name,
                'email' => $psikolog->email,
                'specialization' => $psikolog->psikologProfile?->specialization?->name,
                'status' => $psikolog->psikologProfile?->status,
                'is_available' => $psikolog->psikologProfile?->is_available,
                'rating_avg' => (float) ($psikolog->psikologProfile?->rating_avg ?? 0),
                'total_reviews' => $psikolog->psikologProfile?->total_reviews ?? 0,
                'total_consultations' => $psikolog->psikologProfile?->total_consultations ?? 0,
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'total_income' => (float) $income,
            ];
        });

        return $this->successResponse($stats, 'Laporan psikolog');
    }

    public function bookings(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $totalBookings = Booking::whereBetween('booking_date', [$startDate, $endDate])->count();
        $completedBookings = Booking::where('status', 'completed')
            ->whereBetween('booking_date', [$startDate, $endDate])->count();
        $cancelledBookings = Booking::where('status', 'cancelled')
            ->whereBetween('booking_date', [$startDate, $endDate])->count();
        $rescheduleCount = RescheduleLog::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count();

        // By status
        $byStatus = Booking::selectRaw('status, COUNT(*) as count')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->groupBy('status')
            ->pluck('count', 'status');

        // Daily breakdown
        $dailyBookings = Booking::selectRaw('DATE(booking_date) as date, COUNT(*) as total')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->successResponse([
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'summary' => [
                'total_bookings' => $totalBookings,
                'completed' => $completedBookings,
                'cancelled' => $cancelledBookings,
                'reschedule_count' => $rescheduleCount,
                'completion_rate' => $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 2) : 0,
            ],
            'by_status' => $byStatus,
            'daily_bookings' => $dailyBookings,
        ], 'Laporan booking');
    }
}