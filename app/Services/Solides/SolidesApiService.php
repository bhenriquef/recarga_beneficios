<?php

namespace App\Services\Solides;
use Illuminate\Support\Facades\Http;

class SolidesApiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.solides.url');
        $this->token = config('services.solides.token');
    }

    protected function request($endpoint, $params = [])
    {
        return Http::withToken($this->token)->get("{$this->baseUrl}/{$endpoint}", $params)->json();
    }

    public function getEmployees()
    {
        return $this->request('employees');
    }

    public function getAbsences($startDate, $endDate)
    {
        return $this->request('absences', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function getVacations()
    {
        return $this->request('vacations');
    }

    public function getTerminations()
    {
        return $this->request('terminations');
    }
}
