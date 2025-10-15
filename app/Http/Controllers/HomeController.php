<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $contents = Contents::latest('published_at')->paginate(20);
        $churches = Church::all();
        $departments = Department::all();

        // For infinite scroll requests, only return contents data
        if ($request->wantsJson() || $request->header('X-Inertia-Partial-Data')) {
            return Inertia::render('Welcome', [
                'contents' => $contents,
                'churches' => $churches,
                'departments' => $departments,
            ]);
        }

        return Inertia::render('Welcome', [
            'contents' => $contents,
            'churches' => $churches,
            'departments' => $departments,
        ]);
    }
}
