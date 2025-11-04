<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolidesService;
use App\Services\VrBeneficiosService;
use App\Exports\VRExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\IfoodExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\{
    Employee,
    Company,
    Benefit,
    EmployeeBenefit,
    Absenteeism,
    Holiday,
    Credit,
    EmployeesBenefits,
    EmployeesBenefitsMonthly,
    Workday
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ExportController extends Controller
{
    public function generate(Request $request, SolidesService $solides, VrBeneficiosService $vr)
    {
        try {
            // Períodos de referência
            $inicioMes = Carbon::now()->startOfMonth();

            // Dias úteis de 16/mês atual até 15/mês seguinte
            $diasUteis = calcularDiasUteisComSabado(
                Carbon::now()->day(16),
                Carbon::now()->addMonth()->day(15)
            );

            $employees = Employee::
            selectRaw('employees.*, companies.id as company_cnpj')
            ->join('companies', 'companies.id', '=', 'employees.company_id')
            ->where('active', true)
            ->get();

            $inicio_mes_util = Carbon::now()->day(16)->startOfDay()->format('Y-m-d');
            $fim_mes_util = Carbon::now()->addMonth()->day(15)->startOfDay()->format('Y-m-d');

            // gera array de dias trabalhados por funcionario nesse mes
            $workDays = Workday::where('date', Carbon::now()->subMonth()->day(1)->format('Y-m-d'))->pluck('calc_days', 'employee_id')->toArray();
            $holidays = Holiday::where(function($query) use($inicio_mes_util, $fim_mes_util){
                $query->whereBetween('start_date', [$inicio_mes_util, $fim_mes_util])
                ->orWhereBetween('end_date', [$inicio_mes_util, $fim_mes_util]);
            })
            ->get()
            ->keyBy('employee_id')
            ->map(fn($item) => [
                'start_date' => $item->start_date,
                'end_date' => $item->end_date,
            ])
            ->toArray();

            $diasTrabalhados = $diasUteis;
            $dadosPlanilhaVR = [];
            $dadosPlanilhaIfood = [];

            foreach ($employees as $employee) {
                // pega dias trabalhados do banco
                if(isset($workDays[$employee->id])){
                    $diasTrabalhados = $workDays[$employee->id];

                    if(isset($holidays[$employee->id])){ // calcular dias trabalhados - dias de ferias (apenas dias uteis)
                        // caso o inicio do mes util seja menor que o inicio das ferias, calculamos pelo inicio das ferias, senao pegamos o inicio do dia util
                        $inicio = $holidays[$employee->id]['start_date'] > $inicio_mes_util ? $holidays[$employee->id]['start_date'] : $inicio_mes_util;
                        // caso o fim do mes util seja maior que o fim das ferias, calculamos pelo fim das ferias, senao pegamos o fim do mes util.
                        $fim = $holidays[$employee->id]['end_date'] < $fim_mes_util ? $holidays[$employee->id]['end_date'] : $fim_mes_util;

                        // ... fizemos dessa forma pois caso o inicio/fim das ferias nao se enquadre nos dias que calculamos de mes util (15(esse mes) a 16 (mes seguinte))
                        // entao vamos pegar o inicio/fim do mes util, assim apenas calculamos as ferias que entram neste mes.

                        $uteisFerias = calcularDiasUteisComSabado(
                            Carbon::parse($inicio),
                            Carbon::parse($fim)
                        );

                        $diasTrabalhados = $diasTrabalhados - $uteisFerias;
                    }
                }

                // gera dados da planilha vr
                $dadosPlanilhaVR[] = [
                    $employee->cod_vr => $diasTrabalhados,
                ];

                // ifood = dias trabalhados * 10;
                $valorTotalIfood = $diasTrabalhados * 10;

                // formatando data pro excel do ifood
                $birthday = $employee->birthday ? Carbon::parse($employee->birthday)->format('d/m/Y') : null;

                // gera dados da planilha ifood
                $dadosPlanilhaIfood[] = [
                    'cnpj' => $employee->company_cnpj,
                    'nome' => $employee->full_name,
                    'cpf' => $employee->cpf,
                    'data_nascimento' => $birthday,
                    'livre' => $valorTotalIfood,
                ];
            }

            // Excel ifood
            $fileNameIfood = 'planilha_ifood_' . Carbon::now()->format('mY') . '.xls';
            $pathIfood = "exports/ifood/{$fileNameIfood}";
            Storage::makeDirectory('exports/ifood');

            if (Storage::disk('local')->exists($pathIfood)) {
                Storage::disk('local')->delete($pathIfood);
                Log::info("Arquivo antigo do iFood removido antes de gerar novo: {$pathIfood}");
            }

            Excel::store(new IfoodExport($dadosPlanilhaIfood), "exports/ifood/{$fileNameIfood}");
            Excel::download(new IfoodExport($dadosPlanilhaIfood), $fileNameIfood);

            // gera planilha vr
            $this->generateVRSheet($dadosPlanilhaVR);

            return response()->json(['Success' => 'Excel gerado!'], 200);
        } catch (\Throwable $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function check()
    {
        $mesAtual = Carbon::now()->format('mY');

        // Caminhos dos arquivos
        $ifoodPath = "exports/ifood/planilha_ifood_{$mesAtual}.xls";
        $vrPath    = "exports/vr/planilha_vr_{$mesAtual}.xls";

        // Verifica existência
        $exists = [
            'ifood' => Storage::disk('local')->exists($ifoodPath),
            'vr'    => Storage::disk('local')->exists($vrPath),
        ];

        // URLs públicas (usando response()->download)
        $urls = [
            'ifood' => $exists['ifood'] ? route('exports.download', ['type' => 'ifood']) : null,
            'vr'    => $exists['vr'] ? route('exports.download', ['type' => 'vr']) : null,
        ];

        return response()->json([
            'exists' => $exists,
            'urls'   => $urls,
        ]);
    }

    public function download($type)
    {
        $mesAtual = Carbon::now()->format('mY');
        $filePath = "exports/{$type}/planilha_{$type}_{$mesAtual}.xls";

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->download(storage_path("app/private/{$filePath}"));
    }

    public function generateVRSheet(array $dados)
    {
        if(!Storage::disk('local')->exists('imports/planilha_vr_referencia.xls')){
            throw new \Exception('Arquivo de referencia "planilha_vr_referencia" não encontrado');
        }

        // Caminho do arquivo recebido (por exemplo, após upload)
        $path = storage_path('app/private/imports/planilha_vr_referencia.xls');

        // Carrega o arquivo original
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('USUARIOS');

        if (!$sheet) {
            throw new \Exception('Aba "USUARIOS" não encontrada no arquivo.');
        }

        // Identifica índices das colunas
        $headerRow = 4;
        $headers = [];
        $lastColumn = $sheet->getHighestColumn();
        $headerCells = $sheet->rangeToArray("A{$headerRow}:{$lastColumn}{$headerRow}", null, true, false)[0];

        foreach ($headerCells as $index => $header) {
            $headers[strtoupper(trim($header))] = $index; // exemplo: ['MATRICULA' => 0, 'DIAS TRABALHADOS' => 5]
        }

        if (!isset($headers['MATRÍCULA*']) || !isset($headers['DIAS TRABALHADOS*'])) {
            throw new \Exception('As colunas MATRICULA e/ou DIAS TRABALHADOS não foram encontradas.');
        }

        // Percorre todas as linhas e atualiza os valores
        $rowCount = $sheet->getHighestDataRow();

        for ($row = 5; $row <= $rowCount; $row++) {
            $matricula = trim($sheet->getCellByColumnAndRow($headers['MATRÍCULA*'] + 1, $row)->getValue());

            if (isset($dados[$matricula])) {
                $sheet->setCellValueByColumnAndRow($headers['DIAS TRABALHADOS*'] + 1, $row, $dados[$matricula]);
            }
        }

        // Excel VR
        $fileNameVR = 'planilha_vr_' . Carbon::now()->format('mY') . '.xls';
        Storage::makeDirectory('exports/vr');
        $pathVR = Storage::path("private/exports/vr/{$fileNameVR}");

        if (Storage::disk('local')->exists($pathVR)) {
            Storage::disk('local')->delete($pathVR);
            Log::info("🗑️ Arquivo antigo do VR removido antes de gerar novo: {$pathVR}");
        }

        // Salva nova versão no storage
        $outputPath = storage_path("app/private/exports/vr/{$fileNameVR}");

        $writer = new Xls($spreadsheet);
        $writer->save($outputPath);

        return response()->download($outputPath);
    }
}
