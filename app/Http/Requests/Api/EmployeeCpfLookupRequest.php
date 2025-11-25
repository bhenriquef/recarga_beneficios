<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeCpfLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'month' => trim((string) $this->input('month')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'regex:/^\\d{11}$/'],
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.required' => 'O CPF e obrigatorio.',
            'cpf.regex' => 'Informe um CPF valido com 11 digitos.',
            'month.required' => 'O mes e obrigatorio.',
            'month.date_format' => 'Informe o mes no formato YYYY-MM.',
        ];
    }
}
