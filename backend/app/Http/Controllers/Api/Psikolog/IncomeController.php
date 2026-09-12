<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $psikolog = $request->user();

        $totalIncome = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->sum('calculated_price');

        $monthlyIncome = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->sum('calculated_price');

        $weeklyIncome = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('calculated_price');

        $totalCompleted = Order::where('psikolog_id', $psikolog->id)
            ->where('status', 'completed')
            ->count();

        return $this->successResponse([
            'total_income' => (float) $totalIncome,
            'monthly_income' => (float) $monthlyIncome,
            'weekly_income' => (float) $weeklyIncome,
            'total_completed_consultations' => $totalCompleted,
        ], 'Ringkasan pendapatan');
    }

    public function report(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $orders = Order::where('psikolog_id', $request->user()->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['pasien', 'category', 'duration'])
            ->orderBy('updated_at', 'desc')
            ->paginate(min($request->get('per_page', 15), 50));

        return $this->paginateResponse($orders, 'Laporan pendapatan', OrderResource::class);
    }
}