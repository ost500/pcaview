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

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsIds = \DB::table('contents')
            ->join('content_department', 'contents.id', '=', 'content_department.content_id')
            ->where('content_department.department_id', $id)
            ->select(\DB::raw('MAX(contents.id) as id'))
            ->groupBy('contents.department_id', 'contents.title')
            ->pluck('id');

        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
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

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsIds = \DB::table('contents')
            ->join('content_department', 'contents.id', '=', 'content_department.content_id')
            ->where('content_department.department_id', $department->id)
            ->select(\DB::raw('MAX(contents.id) as id'))
            ->groupBy('contents.department_id', 'contents.title')
            ->pluck('id');

        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
            ->whereHas('departments', function ($query) use ($department) {
                $query->where('departments.id', $department->id);
            })
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('department/Show', ['department' => $department, 'contents' => $contents]);
    }

    // 모바일 전용 부서 상세 페이지 (헤더 없음)
    public function mobileShow(int $id)
    {
        $department = Department::with('church')->findOrFail($id);

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsIds = \DB::table('contents')
            ->join('content_department', 'contents.id', '=', 'content_department.content_id')
            ->where('content_department.department_id', $id)
            ->select(\DB::raw('MAX(contents.id) as id'))
            ->groupBy('contents.department_id', 'contents.title')
            ->pluck('id');

        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
            ->whereHas('departments', function ($query) use ($id) {
                $query->where('departments.id', $id);
            })
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('mobile/department/Show', ['department' => $department, 'contents' => $contents]);
    }
}
