<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentsType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateVideoThumbnail;
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
            $query->where('title', 'like', "%{$search}%");
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
        }
        unset($validated['thumbnail']);

        // Video 업로드 (file_url로 저장)
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('feed-videos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['file_url'] = Storage::disk('s3')->url($path);
            $validated['file_type'] = 'video';
        }
        unset($validated['video']);

        // Images 업로드 (body에 JSON으로 저장)
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('content-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
            $validated['body'] = json_encode(['images' => $imageUrls]);
        }
        unset($validated['images']);

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

        // Video가 업로드된 경우 썸네일 생성 Job 디스패치
        if ($content->file_type === 'video' && $content->file_url) {
            GenerateVideoThumbnail::dispatch($content);
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

        // Video 업로드 (file_url로 저장)
        if ($request->hasFile('video')) {
            if ($content->file_url && $content->file_type === 'video') {
                $oldPath = parse_url($content->file_url, PHP_URL_PATH);
                if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }
            $path = $request->file('video')->store('feed-videos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $validated['file_url'] = Storage::disk('s3')->url($path);
            $validated['file_type'] = 'video';
            unset($validated['video']);
        }

        // Images 업로드 (body에 JSON으로 저장)
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('content-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
            $validated['body'] = json_encode(['images' => $imageUrls]);
            unset($validated['images']);
        }

        // departments 배열 분리
        $departmentIds = $validated['departments'] ?? [];
        unset($validated['departments']);

        $content->update($validated);

        // Many-to-many 관계 동기화
        if (!empty($departmentIds)) {
            $content->departments()->sync($departmentIds);
        }

        // Video가 업로드된 경우 썸네일 생성 Job 디스패치
        if ($content->file_type === 'video' && $content->file_url && !$content->thumbnail_url) {
            GenerateVideoThumbnail::dispatch($content);
        }

        return redirect()->route('admin.contents.index')
            ->with('success', 'Content updated successfully');
    }

    public function destroy(Contents $content)
    {
        // Thumbnail 삭제
        if ($content->thumbnail_url) {
            $oldPath = parse_url($content->thumbnail_url, PHP_URL_PATH);
            if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                Storage::disk('s3')->delete($oldPath);
            }
        }

        // Video 파일 삭제 (file_url)
        if ($content->file_url && $content->file_type === 'video') {
            $oldPath = parse_url($content->file_url, PHP_URL_PATH);
            if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
                Storage::disk('s3')->delete($oldPath);
            }
        }

        $content->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Content deleted successfully');
    }
}
