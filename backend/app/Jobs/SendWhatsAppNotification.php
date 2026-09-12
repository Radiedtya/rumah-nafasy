<?php

namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $phone,
        public string $message
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        $fonnte->send($this->phone, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error(
            "WhatsApp job failed for {$this->phone}: " . $exception->getMessage()
        );
    }
}