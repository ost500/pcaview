<?php

namespace App\Http\Controllers;

use App\Models\Church;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChurchController extends Controller
{
    public function index()
    {
        $churches = Church::all();

        return Inertia::render('church/Index', ['churches' => $churches]);
    }

    public function show(int $id)
    {
        $church = Church::find($id);

        return Inertia::render('church/Show', ['church' => $church]);
    }
}
