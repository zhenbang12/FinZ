<?php

namespace App\Console\Commands;

use App\Services\DemoDataService;
use Illuminate\Console\Command;

class SeedDemoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finz:demo-seed {--force : Force reset and re-seed demo data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed or refresh comprehensive demonstration data for the FinZ Demo user';

    /**
     * Execute the console command.
     */
    public function handle(DemoDataService $demoDataService): int
    {
        $this->info('Initializing FinZ demonstration data...');

        $force = (bool) $this->option('force');
        $user = $demoDataService->ensureDemoData($force);

        $this->info("Demo user verified: {$user->email} (ID: {$user->id})");
        $this->info("Accounts: " . $user->accounts()->count());
        $this->info("Transactions: " . $user->transactions()->count());
        $this->info("Receipts: " . $user->receipts()->count());

        $token = config('demo.token', env('DEMO_ACCESS_TOKEN', 'finz-preview-access'));
        $demoUrl = url('/demo' . (!empty($token) ? '?token=' . $token : ''));

        $this->newLine();
        $this->info("==========================================");
        $this->info(" FinZ Demo Link:");
        $this->comment(" {$demoUrl}");
        $this->info("==========================================");

        return self::SUCCESS;
    }
}
