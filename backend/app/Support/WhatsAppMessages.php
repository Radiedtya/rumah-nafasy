<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;

class WhatsAppMessages
{
    private static function header(): string
    {
        return "🏠 *Rumah Natasy - E-Konseling*\n\n";
    }

    private static function formatPrice(float $price): string
    {
        return 'Rp ' . number_format($price, 0, ',', '.');
    }

    private static function formatDate($date): string
    {
        $days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $carbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        return $days[$carbon->dayOfWeek] . ', ' . $carbon->day . ' ' . $months[$carbon->month - 1] . ' ' . $carbon->year;
    }

    // ============================================
    // ORDER & PAYMENT
    // ============================================

    public static function orderCreated(Order $order): string
    {
        return self::header()
            . "🎉 *Pesanan Dibuat!*\n\n"
            . "No. Pesanan: *{$order->order_number}*\n"
            . "Psikolog: {$order->psikolog->name}\n"
            . "Harga: " . self::formatPrice($order->calculated_price) . "\n\n"
            . "⚠️ Silakan bayar dalam 24 jam.\n"
            . "Login ke dashboard untuk pembayaran.";
    }

    public static function paymentSuccessPasien(Order $order): string
    {
        return self::header()
            . "✅ *Pembayaran Diterima!*\n\n"
            . "No. Pesanan: *{$order->order_number}*\n"
            . "Psikolog: {$order->psikolog->name}\n"
            . "Harga: " . self::formatPrice($order->calculated_price) . "\n\n"
            . "Silakan pilih jadwal konsultasi dalam 7 hari.\n"
            . "Login ke dashboard untuk memilih jadwal.";
    }

    public static function paymentSuccessPsikolog(Order $order): string
    {
        return self::header()
            . "🔔 *Booking Baru!*\n\n"
            . "Pasien: {$order->pasien->name}\n"
            . "No. Pesanan: {$order->order_number}\n"
            . "Tipe: " . ucfirst($order->consultation_type) . "\n\n"
            . "Pasien sedang memilih jadwal konsultasi.";
    }

    // ============================================
    // BOOKING (JADWAL)
    // ============================================

    public static function bookingConfirmed(Booking $booking): string
    {
        $date = self::formatDate($booking->booking_date);

        return self::header()
            . "📅 *Jadwal Konsultasi Dikonfirmasi!*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Tanggal: {$date}\n"
            . "Jam: {$booking->start_time} - {$booking->end_time} WIB\n"
            . "Tipe: " . ucfirst($booking->order->consultation_type) . "\n\n"
            . "Room ID: {$booking->room_id}\n\n"
            . "💡 Link join konsultasi akan aktif saat jam konsultasi.";
    }

    public static function bookingConfirmedPsikolog(Booking $booking): string
    {
        $date = self::formatDate($booking->booking_date);

        return self::header()
            . "📅 *Jadwal Konsultasi Baru*\n\n"
            . "Pasien: {$booking->pasien->name}\n"
            . "Tanggal: {$date}\n"
            . "Jam: {$booking->start_time} - {$booking->end_time} WIB\n"
            . "Tipe: " . ucfirst($booking->order->consultation_type) . "\n\n"
            . "Siapkan diri untuk konsultasi.";
    }

    // ============================================
    // RESCHEDULE
    // ============================================

    public static function rescheduled(Booking $booking): string
    {
        $date = self::formatDate($booking->booking_date);

        return self::header()
            . "🔄 *Jadwal Diubah*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Tanggal baru: {$date}\n"
            . "Jam baru: {$booking->start_time} - {$booking->end_time} WIB\n\n"
            . "Jadwal lama telah diganti dengan jadwal baru di atas.";
    }

    // ============================================
    // CANCEL & REFUND
    // ============================================

    public static function bookingCancelled(Booking $booking, Refund $refund = null): string
    {
        $msg = self::header()
            . "❌ *Booking Dibatalkan*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Tanggal: " . self::formatDate($booking->booking_date) . "\n"
            . "Jam: {$booking->start_time} - {$booking->end_time} WIB\n\n";

        if ($refund && $refund->amount > 0) {
            $percentage = round(($refund->amount / $booking->order->calculated_price) * 100);
            $msg .= "💰 Refund: " . self::formatPrice($refund->amount) . " ({$percentage}%)\n"
                . "Status: {$refund->status}\n\n"
                . "Dana akan dikembalikan dalam 3-5 hari kerja.";
        } else {
            $msg .= "Tidak ada refund (cancel kurang dari 24 jam).";
        }

        return $msg;
    }

    public static function refundApproved(Refund $refund): string
    {
        return self::header()
            . "💰 *Refund Disetujui!*\n\n"
            . "Jumlah: " . self::formatPrice($refund->amount) . "\n"
            . "No. Pesanan: {$refund->order->order_number}\n\n"
            . "Dana akan dikembalikan ke metode pembayaran asli dalam 3-5 hari kerja.";
    }

    // ============================================
    // CONSULTATION
    // ============================================

    public static function consultationCompleted(Booking $booking): string
    {
        return self::header()
            . "✅ *Konsultasi Selesai*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Tanggal: " . self::formatDate($booking->booking_date) . "\n\n"
            . "Terima kasih telah berkonsultasi! 🙏\n"
            . "Mohon berikan review untuk psikolog Anda.\n"
            . "Login ke dashboard untuk memberi review.";
    }

    // ============================================
    // REMINDERS (untuk scheduler Step 9)
    // ============================================

    public static function reminderH1(Booking $booking): string
    {
        $date = self::formatDate($booking->booking_date);

        return self::header()
            . "⏰ *Reminder Konsultasi Besok*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Tanggal: {$date}\n"
            . "Jam: {$booking->start_time} - {$booking->end_time} WIB\n\n"
            . "Bersiap untuk konsultasi besok! 💪";
    }

    public static function reminderH1Psikolog(Booking $booking): string
    {
        $date = self::formatDate($booking->booking_date);

        return self::header()
            . "⏰ *Reminder: Ada Konsultasi Besok*\n\n"
            . "Pasien: {$booking->pasien->name}\n"
            . "Tanggal: {$date}\n"
            . "Jam: {$booking->start_time} - {$booking->end_time} WIB\n\n"
            . "Bersiap untuk konsultasi besok!";
    }

    public static function reminder1Hour(Booking $booking): string
    {
        return self::header()
            . "🚨 *Konsultasi dalam 1 Jam!*\n\n"
            . "Psikolog: {$booking->psikolog->name}\n"
            . "Jam: {$booking->start_time} WIB\n\n"
            . "Konsultasi akan dimulai dalam 1 jam.\n"
            . "Silakan siap dan tunggu link join.";
    }

    public static function reminder1HourPsikolog(Booking $booking): string
    {
        return self::header()
            . "🚨 *Konsultasi dalam 1 Jam!*\n\n"
            . "Pasien: {$booking->pasien->name}\n"
            . "Jam: {$booking->start_time} WIB\n\n"
            . "Konsultasi akan dimulai dalam 1 jam.\n"
            . "Siap untuk memulai konsultasi.";
    }

    // ============================================
    // NO SCHEDULE REMINDER
    // ============================================

    public static function noScheduleReminder(Order $order, int $daysLeft): string
    {
        $urgency = $daysLeft <= 1 ? "🚨 URGENT!" : "⏰ Reminder";

        return self::header()
            . "{$urgency} *Pilih Jadwal Konsultasi*\n\n"
            . "No. Pesanan: {$order->order_number}\n"
            . "Psikolog: {$order->psikolog->name}\n\n"
            . "Anda belum memilih jadwal konsultasi.\n"
            . "Sisa waktu: *{$daysLeft} hari*\n\n"
            . "Segera pilih jadwal sebelum kedaluwarsa!\n"
            . "Login ke dashboard untuk memilih jadwal.";
    }

    // ============================================
    // ORDER EXPIRED
    // ============================================

    public static function orderExpired(Order $order): string
    {
        return self::header()
            . "⚠️ *Pesanan Kedaluwarsa*\n\n"
            . "No. Pesanan: {$order->order_number}\n"
            . "Psikolog: {$order->psikolog->name}\n\n"
            . "Pesanan Anda telah kedaluwarsa (tidak memilih jadwal dalam 7 hari).\n"
            . "Refund otomatis sedang diproses.\n"
            . "Dana akan dikembalikan dalam 3-5 hari kerja.";
    }

    // ============================================
    // PSIKOLOG VERIFIED
    // ============================================

    public static function psikologVerified(User $psikolog): string
    {
        return self::header()
            . "✅ *Akun Terverifikasi!*\n\n"
            . "Selamat {$psikolog->name}!\n\n"
            . "Akun psikolog Anda telah terverifikasi.\n"
            . "Anda sekarang dapat:\n"
            . "• Menerima booking konsultasi\n"
            . "• Mengatur jadwal praktek\n"
            . "• Melakukan konsultasi online\n\n"
            . "Silakan lengkapi profil dan atur jadwal praktek Anda.";
    }
}