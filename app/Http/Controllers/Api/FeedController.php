<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contents;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $contents = Contents::with(['user', 'church', 'departments', 'images'])
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return response()->json($contents);
    }
}
