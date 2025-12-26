<?php

namespace App\Http\Controllers;

use App\Imports\DadosReaproveitamentoImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MultiSheetImport;
use App\Imports\SaldoLivreIfoodImport;
use App\Imports\SaldoMobilidadeIfoodImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'type' => 'required|in:funcionarios_vr,dados_reaproveitamento,saldo_livre_ifood,saldo_mobilidade_ifood',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $type = $request->input('type');
        $file = $request->file('file');

        $ext = strtolower($file->getClientOriginalExtension()); // xlsx, xls, csv

        $fileNameByType = [
            'funcionarios_vr' => 'planilha_vr_referencia',         // sem extensão aqui
            'dados_reaproveitamento' => 'dados_reaproveitamento',
            'saldo_livre_ifood' => 'saldo_livre_ifood',
            'saldo_mobilidade_ifood' => 'saldo_mobilidade_ifood',
        ];

        $fileBase = $fileNameByType[$type] ?? ('import_' . now()->format('Ymd_His'));
        $fileName = "{$fileBase}.{$ext}";

        // se você usa disco private, especifique ele aqui
        $path = $file->storeAs('imports', $fileName);
        $fullPath = storage_path('app/private/' . $path);

        try {
            switch ($type) {
                case 'funcionarios_vr':
                    // Continua exatamente como hoje (multisheet 3 abas)
                    Excel::import(new MultiSheetImport, $fullPath);
                    break;

                case 'dados_reaproveitamento':
                    Excel::import(new DadosReaproveitamentoImport, $fullPath);
                    break;

                case 'saldo_livre_ifood':
                    Excel::import(new SaldoLivreIfoodImport, $fullPath);
                    break;

                case 'saldo_mobilidade_ifood':
                    Excel::import(new SaldoMobilidadeIfoodImport, $fullPath);
                    break;
            }

            return redirect()->back()->with('success', "Arquivo importado com sucesso como {$fileName}.");
        } catch (\Throwable $e) {
            Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'type' => $type]);
            return redirect()->back()->with('error', 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage());
        }
    }

    public function syncStatus()
    {
        $progress = Cache::get('sync_progress', 0);
        $error    = Cache::get('sync_error', null);

        return response()->json([
            'finished' => ((int)$progress) >= 100,
            'error'    => $error,
        ]);
    }


    public function runSyncDatabase()
    {
        try {
            if (!function_exists('exec')) {
                Log::error('sync:database n�o p�de iniciar: fun��o exec desabilitada');
                return response()->json([
                    'success' => false,
                    'message' => 'exec desabilitado no servidor.',
                ], 500);
            }

            Cache::put('sync_progress', 0);
            Cache::put('sync_logs', []);
            Cache::put('sync_error', null);
            Cache::put('sync_started_at', now()->timestamp);

            $php  = env('PHP_PATH', '/usr/bin/php');
            $base = base_path();

            if (env('APP_ENV') === 'local') {
                $cmd = "cmd /C \"cd {$base} && start \"\" /B \"{$php}\" artisan sync:database --stream\"";
                Log::info("CMD executado (local): $cmd");

                pclose(popen($cmd, 'r'));
                Log::info('Processo de sync iniciado via start /B');
            } else {
                $cmd = "cd {$base} && {$php} artisan sync:database --stream > /dev/null 2>&1 &";
                Log::info("CMD executado (linux): $cmd");
                exec($cmd, $output, $exitCode);

                if (isset($exitCode) && $exitCode !== 0) {
                    Cache::put('sync_error', 'Falha ao disparar o processo de sync (exitCode ' . $exitCode . ').');
                    Log::error("sync:database n�o iniciou (exitCode={$exitCode})", ['output' => $output]);
                }
            }

            return response()->json(['started' => true]);
        } catch (\Throwable $e) {
            Log::error('Erro ao iniciar processo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar sincroniza��o.',
            ], 500);
        }
    }
}
