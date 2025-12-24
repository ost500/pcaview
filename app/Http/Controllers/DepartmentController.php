<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();

        return Inertia::render('department/Index', ['departments' => $departments]);
    }

    public function show(int $id)
    {
        $department = Department::find($id);
        $contents = Contents::with('department')
            ->withCount('comments')
            ->where('department_id', $id)
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('department/Show', ['department' => $department, 'contents' => $contents]);
    }

    public function keyword(string $keyword)
    {
        $department = Department::where('name', $keyword)->first();
        $contents = Contents::with('department')
            ->withCount('comments')
            ->where('department_id', $department->id)
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('department/Show', ['department' => $department, 'contents' => $contents]);
    }
}
