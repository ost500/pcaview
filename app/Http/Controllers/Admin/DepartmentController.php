<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('church')->paginate(10);

        return Inertia::render('admin/departments/Index', [
            'departments' => $departments,
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
            'icon_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($request->hasFile('icon_image')) {
            // 기존 이미지 삭제 (public 디스크에 있는 경우)
            if ($department->icon_image && Storage::disk('public')->exists($department->icon_image)) {
                Storage::disk('public')->delete($department->icon_image);
            }

            // 새 이미지 저장
            $path = $request->file('icon_image')->store('department-icons', 'public');
            $validated['icon_image'] = Storage::disk('public')->url($path);
        }

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully');
    }
}
