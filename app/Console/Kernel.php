<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Definição dos comandos customizados da aplicação
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    /**
     * Agendamento de tarefas automáticas (cron)
     */
    protected function schedule(Schedule $schedule)
    {
        // Exemplo: gerar relatório todo dia 22 à meia-noite
        $schedule->command('benefits:generate-report')
                 ->monthlyOn(22, '00:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/benefit_report.log'));
    }
}
