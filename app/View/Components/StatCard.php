<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class StatCard extends Component
{
    public $label;
    public $value;
    public $color;
    public $valueSize;   // ex: text-2xl, text-base
    public $truncate;    // true/false

    public function __construct($label = '', $value = '', $color = 'indigo', $valueSize = 'text-2xl', $truncate = false)
    {
        $this->label = $label;
        $this->value = $value;
        $this->color = $color;
        $this->valueSize = $valueSize;
        $this->truncate = filter_var($truncate, FILTER_VALIDATE_BOOL); // aceita "true"/true
    }

    public function render(): View
    {
        return view('components.stat-card');
    }
}
