<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MultiSheetImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $fileName = 'planilha_vr_referencia.xls';
        $path = $file->storeAs('imports', $fileName);

        try {
            Excel::import(new MultiSheetImport, storage_path('app/private/' . $path));
            return redirect()->back()->with('success', "Arquivo importado com sucesso como {$fileName}.");
        } catch (\Throwable $e) {
            Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage());
        }
    }

    public function runSyncDatabase()
    {
        try {
            if (!function_exists('exec')) {
                Log::error('sync:database não pôde iniciar: função exec desabilitada');
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
                    Log::error("sync:database não iniciou (exitCode={$exitCode})", ['output' => $output]);
                }
            }

            return response()->json(['started' => true]);
        } catch (\Throwable $e) {
            Log::error('Erro ao iniciar processo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar sincronização.',
            ], 500);
        }
    }
}
