<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:daily-admin')]
#[Description('Send daily stats report to admin via WhatsApp')]
class DailyAdminReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteService $fonnte): int
    {
        $admin = User::role('admin')->first();

        if (!$admin) {
            $this->error('No admin user found.');
            return 1;
        }

        $today = now()->format('Y-m-d');

        $newOrders = Order::whereDate('created_at', $today)->count();
        $completedOrders = Order::where('status', 'completed')
            ->whereDate('updated_at', $today)->count();
        $totalRevenue = Order::where('status', 'completed')
            ->whereDate('updated_at', $today)->sum('calculated_price');
        $newUsers = User::whereDate('created_at', $today)->count();
        $activeBookings = Booking::whereDate('booking_date', $today)
            ->whereIn('status', ['confirmed', 'in_progress'])->count();
        $pendingRefunds = Refund::where('status', 'pending')->count();

        $message = "🏠 *Rumah Nafasy - Daily Report*\n\n"
            . "📅 " . now()->format('d M Y') . "\n\n"
            . "📊 *Statistik Hari Ini:*\n"
            . "• Pesanan baru: {$newOrders}\n"
            . "• Konsultasi selesai: {$completedOrders}\n"
            . "• Pendapatan: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n"
            . "• User baru: {$newUsers}\n"
            . "• Booking aktif: {$activeBookings}\n"
            . "• Refund pending: {$pendingRefunds}\n";

        $fonnte->sendNow($admin, $message);

        $this->info("Daily report sent to admin ({$admin->phone}).");
        return 0;
    }
}
