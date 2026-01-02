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
        $departments = Department::with('church')->get();

        return Inertia::render('department/Index', ['departments' => $departments]);
    }

    public function show(int $id)
    {
        $department = Department::with('church')->findOrFail($id);
        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereHas('departments', function ($query) use ($id) {
                $query->where('departments.id', $id);
            })
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('department/Show', ['department' => $department, 'contents' => $contents]);
    }

    public function keyword(string $keyword)
    {
        $department = Department::with('church')->where('name', $keyword)->firstOrFail();
        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereHas('departments', function ($query) use ($department) {
                $query->where('departments.id', $department->id);
            })
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('department/Show', ['department' => $department, 'contents' => $contents]);
    }
}
