<?php

use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('api');

beforeEach(function () {
    // 테스트용 Church 생성
    $this->church = Church::factory()->create([
        'slug' => 'test-church',
        'name' => 'Test Church',
    ]);

    // 테스트용 Department 생성
    $this->department = Department::factory()->create([
        'church_id' => $this->church->id,
        'name'      => 'Test Department',
    ]);

    // 테스트용 User 생성
    $this->user = User::factory()->create();
});

describe('GET /api/c/{church}', function () {
    it('can retrieve contents for a church', function () {
        // contents 생성
        Contents::factory()->count(3)->create([
            'church_id' => $this->church->id,
            'type'      => 'bulletin',
            'is_hide'   => false,
        ]);

        $response = $this->getJson("/api/c/{$this->church->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'church',
                    'contents',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        expect($response->json('data.total'))->toBe(3);
    });

    it('filters news content body to 1/3 of original length', function () {
        // news 타입 콘텐츠 생성 (300자)
        $longBody = str_repeat('뉴스 본문 내용입니다. ', 30); // 약 300자
        Contents::factory()->create([
            'church_id' => $this->church->id,
            'type'      => 'news',
            'body'      => $longBody,
            'is_hide'   => false,
        ]);

        // 일반 콘텐츠 생성
        Contents::factory()->create([
            'church_id' => $this->church->id,
            'type'      => 'bulletin',
            'body'      => $longBody,
            'is_hide'   => false,
        ]);

        $response = $this->getJson("/api/c/{$this->church->slug}");

        $response->assertStatus(200);

        $contents = $response->json('data.contents');

        // news 타입은 body가 1/3로 줄어들어야 함
        $newsContent = collect($contents)->firstWhere('type', 'news');
        expect($newsContent['body'])->toContain('...')
            ->and(mb_strlen($newsContent['body']))->toBeLessThan(mb_strlen($longBody) / 2);

        // 일반 타입은 body가 그대로여야 함
        $bulletinContent = collect($contents)->firstWhere('type', 'bulletin');
        expect($bulletinContent['body'])->toBe($longBody);
    });

    it('filters by department_id when provided', function () {
        $otherDepartment = Department::factory()->create([
            'church_id' => $this->church->id,
        ]);

        // 첫 번째 부서의 콘텐츠
        $content1 = Contents::factory()->create([
            'church_id' => $this->church->id,
            'is_hide'   => false,
        ]);
        $content1->departments()->attach($this->department->id);

        // 두 번째 부서의 콘텐츠
        $content2 = Contents::factory()->create([
            'church_id' => $this->church->id,
            'is_hide'   => false,
        ]);
        $content2->departments()->attach($otherDepartment->id);

        $response = $this->getJson("/api/c/{$this->church->slug}?department_id={$this->department->id}");

        $response->assertStatus(200);

        expect($response->json('data.total'))->toBe(1);
    });

    it('returns 404 for non-existent church', function () {
        $response = $this->getJson('/api/c/non-existent-church');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Church not found',
            ]);
    });

    it('excludes hidden contents', function () {
        Contents::factory()->create([
            'church_id' => $this->church->id,
            'is_hide'   => true,
        ]);

        Contents::factory()->create([
            'church_id' => $this->church->id,
            'is_hide'   => false,
        ]);

        $response = $this->getJson("/api/c/{$this->church->slug}");

        $response->assertStatus(200);
        expect($response->json('data.total'))->toBe(1);
    });
});

describe('GET /api/contents/{id}', function () {
    it('can retrieve a single content', function () {
        $content = Contents::factory()->create([
            'church_id' => $this->church->id,
        ]);

        $response = $this->getJson("/api/contents/{$content->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id' => $content->id,
                ],
            ]);
    });

    it('returns 404 for non-existent content', function () {
        $response = $this->getJson('/api/contents/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Content not found',
            ]);
    });
});

describe('DELETE /api/contents/{id}', function () {
    it('can delete own content with DELETE method', function () {
        $content = Contents::factory()->create([
            'church_id' => $this->church->id,
            'user_id'   => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/contents/{$content->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '콘텐츠가 삭제되었습니다.',
            ]);

        $this->assertDatabaseMissing('contents', ['id' => $content->id]);
    });

    it('can delete own content with POST method', function () {
        $content = Contents::factory()->create([
            'church_id' => $this->church->id,
            'user_id'   => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/contents/{$content->id}/delete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '콘텐츠가 삭제되었습니다.',
            ]);

        $this->assertDatabaseMissing('contents', ['id' => $content->id]);
    });

    it('cannot delete other user content', function () {
        $otherUser = User::factory()->create();
        $content = Contents::factory()->create([
            'church_id' => $this->church->id,
            'user_id'   => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/contents/{$content->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => '삭제 권한이 없습니다.',
            ]);

        $this->assertDatabaseHas('contents', ['id' => $content->id]);
    });

    it('requires authentication to delete content', function () {
        $content = Contents::factory()->create([
            'church_id' => $this->church->id,
            'user_id'   => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/contents/{$content->id}");

        $response->assertStatus(401);
    });

    it('returns 404 for non-existent content', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/contents/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Content not found',
            ]);
    });
});

describe('GET /api/c/{church}/departments', function () {
    it('can retrieve departments for a church', function () {
        Department::factory()->count(3)->create([
            'church_id' => $this->church->id,
        ]);

        $response = $this->getJson("/api/c/{$this->church->slug}/departments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'church',
                    'departments',
                    'total',
                ],
            ]);

        expect($response->json('data.total'))->toBe(4); // 3 + beforeEach에서 생성한 1개
    });

    it('returns 404 for non-existent church', function () {
        $response = $this->getJson('/api/c/non-existent-church/departments');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Church not found',
            ]);
    });
});
