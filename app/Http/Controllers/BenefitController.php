<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $benefits = \App\Models\Benefit::query()
            ->when($search, function ($query, $search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('cod', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('operator', 'like', "%{$search}%");
            })
            ->orderBy('description')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('benefits.index', compact('benefits', 'search'));
    }
}
