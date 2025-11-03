<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SolidesService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.solides.base_url');
        $this->token = config('services.solides.token');
    }

    protected function client()
    {
        return Http::withoutVerifying() // remova isso depois se resolver o SSL
            ->withHeaders([
                'Authorization' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
            // ->baseUrl($this->baseUrl);
    }

    public function getFuncionariosAtivos()
    {
        $response = $this->client()->get('https://employer.tangerino.com.br/employee/find-all', [
            'size' => 1000,
        ]);

        if ($response->failed()) {
            logger()->error('Erro ao buscar colaboradores Solides', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("Erro na API Solides: {$response->status()}");
        }

        return $response->json()['content'] ?? [];
    }

    public function getDiasTrabalhados($id, $inicio, $fim){
        $response = $this->client()->get('https://apis.tangerino.com.br/punch', [
            'employeeId' => $id,
            'startDate' => $inicio,
            'endDate' => $fim,
            'size' => 1000,
        ]);

        if ($response->failed()) {
            logger()->error('Erro ao buscar dias trabalhados na Solides', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("Erro na API Solides: {$response->status()}");
        }

        return $response->json()['content'] ?? [];
    }

    public function getEmpresas(){
        $response = $this->client()->get('https://employer.tangerino.com.br/companies');

        if ($response->failed()) {
            logger()->error('Erro ao buscar empresas na Solides', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("Erro na API Solides: {$response->status()}");
        }

        return $response->json()['content'] ?? [];
    }

    // essa auth serve apenas para pegar os dados de ferias da api da tangerino
    public function apiTangerinoAuth(){
        $response = Http::withoutVerifying() // remova isso depois se resolver o SSL
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'INTEGRATION_TOKEN' => config('services.solides.integration_token'),
                'TNG-CLIENT-TOKEN' => config('services.solides.token_n_basic'),
            ])->post('https://apis.tangerino.com.br/vacation-api/api/v1/auth/token');

        if ($response->failed()) {
            logger()->error('Erro ao gerar auth', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("Erro na API Solides: {$response->status()}");
        }

        return $response->json()['item'] ?? [];
    }

    public function getHolidays($authToken, $page = 1){
        // caso queira testar a request, troque o final do url para ALL
        // ex: https://apis.tangerino.com.br/vacation-api/api/v1/integration/request/ALL
        $response = Http::withoutVerifying() // remova isso depois se resolver o SSL
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'TNG-WEB-TOKEN' => $authToken,
            'TNG-CLIENT-TOKEN' => config('services.solides.token_n_basic'),
        ])->get('https://apis.tangerino.com.br/vacation-api/api/v1/integration/request/APPROVED', [
            'page' => $page,
            'size' => 30, // limite de 30
        ]);

        $response_json = $response->json();

        if ($response->failed() && $response_json['error'] != 'Nenhum dado foi encontrado.') {
            logger()->error('Erro ao gerar auth', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("Erro na API Solides: {$response->status()}");
        }

        return isset($response_json['item']) ? $response_json['item'] : [];
    }
}
