<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MultiSheetImport;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        $file = $request->file('file');

        // Salvar temporariamente em storage/app/imports
        // $path = $file->store('imports');

        // dd($file, $path, storage_path('app/' . $path));

        try {
            // Excel::import(new MultiSheetImport(auth()->user()), storage_path('app/private' . $path));
            Excel::import(new MultiSheetImport, $file); // ambas as formas funcionam
            return redirect()->back()->with('success', 'Arquivo importado com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage());
        }
    }
}
