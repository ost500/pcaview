<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ChurchController extends Controller
{
    public function index(Request $request)
    {
        $query = Church::with('primaryDepartment');

        // 검색 기능
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $churches = $query->paginate(20)->withQueryString();

        return Inertia::render('admin/churches/Index', [
            'churches' => $churches,
            'filters'  => [
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function edit(Church $church)
    {
        $church->load('primaryDepartment');
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('admin/churches/Edit', [
            'church'      => $church,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, Church $church)
    {
        $validated = $request->validate([
            'name'                  => 'sometimes|string|max:255',
            'slug'                  => 'sometimes|string|max:255|unique:churches,slug,'.$church->id,
            'description'           => 'nullable|string',
            'primary_department_id' => 'nullable|exists:departments,id',
            'icon_image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($request->hasFile('icon_image')) {
            // 기존 이미지 삭제
            if ($church->icon_image) {
                $oldPath = parse_url($church->icon_image, PHP_URL_PATH);
                if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }

            // 새 이미지 저장 (S3)
            $path = $request->file('icon_image')->store('church-icons', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['icon_image'] = Storage::disk('s3')->url($path);
        }

        $church->update($validated);

        return redirect()->route('admin.churches.index')
            ->with('success', 'Church updated successfully');
    }
}
