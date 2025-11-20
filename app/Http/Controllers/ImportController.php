<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MultiSheetImport;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Cache;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        $file = $request->file('file');

        // Gera nome personalizado
        // Exemplo: planilha_import_2025_10_29_1503.xlsx
        $extension = $file->getClientOriginalExtension();
        $fileName = 'planilha_vr_referencia.xls';

        // Salva com nome customizado
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
            Cache::put('sync_progress', 0);
            Cache::put('sync_logs', []);

            $php = env('PHP_PATH', 'C:\php\php.exe');
            $base = base_path();

            // RODA EM BACKGROUND COM --stream
            $cmd = "cmd /C \"cd {$base} && start \"\" /B \"{$php}\" artisan sync:database --stream\"";

            Log::info("🔧 CMD executado: $cmd");

            // inicia processo sem bloquear e sem abrir janela
            pclose(popen($cmd, 'r'));

            Log::info('🔥 Processo de sync iniciado via start /B');

            return response()->json(['started' => true]);

        } catch (\Throwable $e) {
            Log::error("❌ Erro ao iniciar processo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar sincronização.'
            ], 500);
        }
    }



}
