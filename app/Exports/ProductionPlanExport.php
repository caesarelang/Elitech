<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductionPlanExport implements FromView
{
    protected $plans;

    public function __construct($plans)
    {
        $this->plans = $plans;
    }

    public function view(): View
    {
        return view('exports.production_plan', [
            'plans' => $this->plans
        ]);
    }
}
