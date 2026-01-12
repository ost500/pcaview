<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with('church');

        // 검색 기능
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('church', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $departments = $query->paginate(20)->withQueryString();

        return Inertia::render('admin/departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function edit(Department $department)
    {
        $department->load('church');

        return Inertia::render('admin/departments/Edit', [
            'department' => $department,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_crawl' => 'nullable|boolean',
            'icon_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($request->hasFile('icon_image')) {
            // 기존 이미지 삭제
            if ($department->icon_image) {
                $oldPath = parse_url($department->icon_image, PHP_URL_PATH);
                if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }

            // 새 이미지 저장 (S3)
            $path = $request->file('icon_image')->store('department-icons', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['icon_image'] = Storage::disk('s3')->url($path);
        }
        unset($validated['icon_image']);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully');
    }
}
