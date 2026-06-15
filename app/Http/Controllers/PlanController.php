<?php

namespace App\Http\Controllers;

use App\Services\TierService;

class PlanController extends Controller
{
    public function __construct(private TierService $tiers) {}

    public function index()
    {
        return view('plans.index', [
            'plans'      => config('tiers'),
            'currentTier' => $this->tiers->tierFor(auth()->user()),
        ]);
    }
}
