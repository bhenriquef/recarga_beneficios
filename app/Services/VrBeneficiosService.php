<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VrBeneficiosService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.vr.base_url');
        $this->token = config('services.vr.token');
    }

    protected function client()
    {
        return Http::withToken($this->token)->baseUrl($this->baseUrl);
    }

    public function getPassagensFuncionario($employeeDocument)
    {
        $response = $this->client()->get("/transporte/{$employeeDocument}");

        $data = $response->json();

        return [
            'quantidade_passagens' => $data['quantidade'] ?? 0,
            'valor_passagem' => $data['valor'] ?? 0.0,
        ];
    }
}
