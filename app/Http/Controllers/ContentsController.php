<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentsController extends Controller
{
    public function show(int $id)
    {
        $contents = Contents::with('images', 'department', 'comments.user')->findOrFail($id);
        return Inertia::render('contents/Show', ['contents' => $contents]);
    }
}
