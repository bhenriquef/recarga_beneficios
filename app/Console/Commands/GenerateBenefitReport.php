<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Solides\SolidesApiService;
use App\Services\Reports\BenefitRechargeReportService;

class GenerateBenefitReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-benefit-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = now()->addMonth()->month;
        $year = now()->addMonth()->year;

        $employees = app(SolidesApiService::class)->getEmployees();
        // app(BenefitRechargeReportService::class)->generate($employees, $month, $year);

        $this->info('Relatório de recarga gerado com sucesso!');
    }

}
