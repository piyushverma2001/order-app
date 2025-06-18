<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderJob;
use App\Models\Order;
use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-pending {--limit=10 : Maximum number of orders to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all pending orders by dispatching jobs';


    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Processing pending orders (limit: {$limit})...");
        
        $pendingOrders = Order::where('status', 'pending')
            ->limit($limit)
            ->get();
        
        if ($pendingOrders->isEmpty()) {
            $this->info('No pending orders found.');
            return 0;
        }
        
        $this->info("Found {$pendingOrders->count()} pending orders.");
        
        $bar = $this->output->createProgressBar($pendingOrders->count());
        $bar->start();
        
        foreach ($pendingOrders as $order) {
            ProcessOrderJob::dispatch($order);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully dispatched {$pendingOrders->count()} jobs for processing.");
        $this->info("Run 'php artisan queue:work' to process the jobs.");
        
        return 0;
    }
} 