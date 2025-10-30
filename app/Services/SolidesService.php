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
}
