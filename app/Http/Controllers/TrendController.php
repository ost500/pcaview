<?php

namespace App\Http\Controllers;

use App\Models\Trend;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrendController extends Controller
{
    public function show(string $keyword)
    {
        $trend = Trend::with('tags')->where('title', $keyword)->firstOrFail();

        return Inertia::render('trend/Show', ['trend' => $trend]);
    }
}
