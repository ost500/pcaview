<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $contents = Contents::latest('published_at')->paginate(10);
        $churches = Church::all();
        $departments = Department::all();
        return Inertia::render('Welcome', [
            'contents' => $contents,
            'churches' => $churches,
            'departments' => $departments,
        ]);
    }
}
