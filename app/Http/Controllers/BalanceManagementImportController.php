<?php

namespace App\Http\Controllers;

use App\Models\BalanceManagement;
use App\Models\Benefit;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BalanceManagementImportController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        return view('balance-management.import');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $collection = Excel::toCollection(null, $request->file('file'));
        $rows = $collection->first();

        if (! $rows || $rows->count() === 0) {
            return back()->with('error', 'Arquivo vazio ou invalido.');
        }

        $header = $rows->first();
        $map = $this->buildHeaderMap($header);

        $required = ['matricula', 'idbeneficio', 'vlrsolicitado', 'sldacumulado', 'vlreconomia', 'vlrfinalpedido', 'competencia'];
        foreach ($required as $key) {
            if (! isset($map[$key])) {
                return back()->with('error', "Coluna obrigatoria nao encontrada: {$key}");
            }
        }

        $inserted = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($rows->skip(1) as $row) {
            $row = $row->toArray();

            $cpfSource = $row[$map['matricula']] ?? null;
            if ($cpfSource === null && isset($map['cpf'])) {
                $cpfSource = $row[$map['cpf']] ?? null;
            }
            $cpf = $this->onlyDigits($cpfSource);
            if ($cpf === '') {
                $skipped++;
                continue;
            }

            $benefitCode = trim((string) ($row[$map['idbeneficio']] ?? ''));
            $requested = $this->parseMoney($row[$map['vlrsolicitado']] ?? null);
            $accumulated = $this->parseMoney($row[$map['sldacumulado']] ?? null);
            $economy = $this->parseMoney($row[$map['vlreconomia']] ?? null);
            $finalOrder = $this->parseMoney($row[$map['vlrfinalpedido']] ?? null);

            $dateValue = $row[$map['competencia']] ?? null;
            if ($dateValue === null && isset($map['data'])) {
                $dateValue = $row[$map['data']] ?? null;
            }
            $date = $this->parseDate($dateValue);

            if (! $date) {
                $skipped++;
                continue;
            }

            $employee = Employee::where('cpf', $cpf)->first();
            $benefit = Benefit::where('cod', $benefitCode)->first();

            if (! $employee || ! $benefit) {
                $skipped++;
                continue;
            }

            $model = BalanceManagement::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'benefits_id' => $benefit->id,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'requested_value' => $requested,
                    'accumulated_balance' => $accumulated,
                    'value_economy' => $economy,
                    'final_order_value' => $finalOrder,
                ]
            );

            $model->wasRecentlyCreated ? $inserted++ : $updated++;
        }

        $msg = "Importacao concluida. Inseridos: {$inserted}. Atualizados: {$updated}. Ignorados: {$skipped}.";
        return back()->with('success', $msg);
    }

    private function authorizeAdmin(): void
    {
        if (! Auth::check() || Auth::user()->type !== 1) {
            abort(403, 'Acesso restrito a administradores.');
        }
    }

    private function buildHeaderMap($header): array
    {
        $map = [];

        foreach ($header as $index => $value) {
            $key = $this->normalizeHeader((string) $value);
            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower($this->stripAccents($value));
        $value = preg_replace('/[^a-z0-9]/', '', $value);
        return $value ?? '';
    }

    private function stripAccents(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        return $transliterated !== false ? $transliterated : $value;
    }

    private function parseMoney($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = str_replace(['.', ' '], '', (string) $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Trata valores numericos vindos como serial do Excel (ex: 45962)
        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable $e) {
                // continua para tentar outros formatos
            }
        }

        $value = trim((string) $value);

        $formats = ['Y-m-d', 'd/m/Y', 'd/m/y', 'm/Y', 'm/y', 'Y-m'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }
}
