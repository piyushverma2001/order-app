<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(
        protected Order $order
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Starting to process order: {$this->order->order_number}");

            $this->order->update(['status' => 'processing']);

            $this->validateInventory();
            $this->processPayment();
            $this->prepareShipping();
            $this->sendNotifications();

            $this->order->markAsProcessed();

            Log::info("Order processed successfully: {$this->order->order_number}");

        } catch (\Exception $e) {
            Log::error("Order processing failed: {$this->order->order_number}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->order->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Order processing job failed permanently: {$this->order->order_number}", [
            'error' => $exception->getMessage(),
            'order_id' => $this->order->id
        ]);

        $this->order->markAsFailed("Job failed after {$this->tries} attempts: " . $exception->getMessage());
    }
} 