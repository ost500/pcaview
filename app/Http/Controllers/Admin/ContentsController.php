<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentsType;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Contents::with(['church', 'department', 'user', 'departments']);

        // 검색 기능
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Church 필터
        if ($churchId = $request->input('church_id')) {
            $query->where('church_id', $churchId);
        }

        // Department 필터
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $contents = $query->latest('published_at')->paginate(20)->withQueryString();
        $churches = Church::select('id', 'name', 'display_name')->orderBy('name')->get();
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('admin/contents/Index', [
            'contents'    => $contents,
            'churches'    => $churches,
            'departments' => $departments,
            'filters'     => [
                'search'        => $request->input('search'),
                'church_id'     => $request->input('church_id'),
                'department_id' => $request->input('department_id'),
            ],
        ]);
    }

    public function create()
    {
        $churches = Church::select('id', 'name', 'display_name')->orderBy('name')->get();
        $departments = Department::select('id', 'name', 'church_id')->orderBy('name')->get();

        return Inertia::render('admin/contents/Create', [
            'churches'    => $churches,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'church_id'     => 'required|exists:churches,id',
            'department_id' => 'nullable|exists:departments,id',
            'departments'   => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'published_at'  => 'nullable|date',
            'thumbnail'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images'        => 'nullable|array',
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'video'         => 'nullable|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-matroska|max:512000',
        ]);

        // Thumbnail 업로드
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['thumbnail_url'] = Storage::disk('s3')->url($path);
            unset($validated['thumbnail']);
        }

        // Video 업로드
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('feed-videos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['video_url'] = Storage::disk('s3')->url($path);
        }
        unset($validated['video']);

        // Images 업로드
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('content-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
            $validated['images'] = json_encode($imageUrls);
        }

        $validated['user_id'] = auth()->id();
        $validated['published_at'] = $validated['published_at'] ?? now();

        // departments 배열 분리
        $departmentIds = $validated['departments'] ?? [];
        unset($validated['departments']);

        $validated['type'] = ContentsType::HTML;

        $content = Contents::create($validated);

        // Many-to-many 관계 동기화
        if (!empty($departmentIds)) {
            $content->departments()->sync($departmentIds);
        }

        return redirect()->route('admin.contents.index')
            ->with('success', 'Content created successfully');
    }

    public function edit(Contents $content)
    {
        $content->load(['church', 'department', 'departments']);
        $churches = Church::select('id', 'name', 'display_name')->orderBy('name')->get();
        $departments = Department::select('id', 'name', 'church_id')->orderBy('name')->get();

        return Inertia::render('admin/contents/Edit', [
            'content'     => $content,
            'churches'    => $churches,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, Contents $content)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'church_id'     => 'required|exists:churches,id',
            'department_id' => 'nullable|exists:departments,id',
            'departments'   => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'published_at'  => 'nullable|date',
            'thumbnail'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images'        => 'nullable|array',
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'video'         => 'nullable|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-matroska|max:512000',
        ]);

        // Thumbnail 업로드
        if ($request->hasFile('thumbnail')) {
            if ($content->thumbnail_url) {
                $oldPath = parse_url($content->thumbnail_url, PHP_URL_PATH);
                if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }
            $path = $request->file('thumbnail')->store('thumbnails', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['thumbnail_url'] = Storage::disk('s3')->url($path);
            unset($validated['thumbnail']);
        }

        // Video 업로드
        if ($request->hasFile('video')) {
            if ($content->video_url) {
                $oldPath = parse_url($content->video_url, PHP_URL_PATH);
                if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }
            $path = $request->file('video')->store('feed-videos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['video_url'] = Storage::disk('s3')->url($path);
            unset($validated['video']);
        }

        // Images 업로드
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('content-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
            $validated['images'] = json_encode($imageUrls);
        }

        // departments 배열 분리
        $departmentIds = $validated['departments'] ?? [];
        unset($validated['departments']);

        $content->update($validated);

        // Many-to-many 관계 동기화
        if (!empty($departmentIds)) {
            $content->departments()->sync($departmentIds);
        }

        return redirect()->route('admin.contents.index')
            ->with('success', 'Content updated successfully');
    }

    public function destroy(Contents $content)
    {
        // 이미지 삭제
        if ($content->thumbnail_url) {
            $oldPath = parse_url($content->thumbnail_url, PHP_URL_PATH);
            if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                Storage::disk('s3')->delete($oldPath);
            }
        }

        if ($content->video_url) {
            $oldPath = parse_url($content->video_url, PHP_URL_PATH);
            if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                Storage::disk('s3')->delete($oldPath);
            }
        }

        $content->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Content deleted successfully');
    }
}
