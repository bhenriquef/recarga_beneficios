<?php

use Carbon\Carbon;

if (!function_exists('calcularDiasUteisComSabado')) {
    function calcularDiasUteisComSabado(Carbon $inicio, Carbon $fim): int
    {
        $dias = 0;
        for ($data = $inicio->copy(); $data->lte($fim); $data->addDay()) {
            if ($data->isSunday()) continue; // pula domingo
            $dias++;
        }
        return $dias;
    }
}
