<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $contents = Contents::latest('published_at')->paginate(100);

        return Inertia::render('Welcome', [
            'contents' => $contents,
        ]);
    }
}
