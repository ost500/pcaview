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

    public function create()
    {
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('admin/churches/Create', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'display_name'          => 'nullable|string|max:255',
            'slug'                  => 'required|string|max:255|unique:churches,slug',
            'description'           => 'nullable|string',
            'address'               => 'nullable|string|max:500',
            'primary_department_id' => 'nullable|exists:departments,id',
            'icon_image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'logo_image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'worship_time_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'address_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        // 이미지 업로드 처리
        if ($request->hasFile('icon_image')) {
            $path = $request->file('icon_image')->store('church-icons', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['icon_image'] = Storage::disk('s3')->url($path);
        }

        if ($request->hasFile('logo_image')) {
            $path = $request->file('logo_image')->store('church-logos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['logo_image'] = Storage::disk('s3')->url($path);
        }

        if ($request->hasFile('worship_time_image')) {
            $path = $request->file('worship_time_image')->store('church-worship-times', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['worship_time_image'] = Storage::disk('s3')->url($path);
        }

        if ($request->hasFile('address_image')) {
            $path = $request->file('address_image')->store('church-addresses', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['address_image'] = Storage::disk('s3')->url($path);
        }

        Church::create($validated);

        return redirect()->route('admin.churches.index')
            ->with('success', 'Church created successfully');
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
