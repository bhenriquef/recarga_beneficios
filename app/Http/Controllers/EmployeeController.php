<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $employees = \App\Models\Employee::query()
            ->when($search, function ($query, $search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cod', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('employees.index', compact('employees', 'search'));
    }
}
