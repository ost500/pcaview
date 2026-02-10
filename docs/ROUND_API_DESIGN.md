# 라운드 시스템 API 설계서

> 파크골프 수첩 앱의 핵심 기능 — 라운드 생성/진행/스코어 입력/완료

---

## 1. 전체 흐름

```
[코스 선택] → [플레이어 설정] → [라운드 생성(draft)]
                                      ↓
                              [라운드 시작(in_progress)]
                                      ↓
                              [홀별 스코어 입력] ← 반복 (1홀~9홀)
                                      ↓
                              [라운드 완료(completed)]
                                      ↓
                              [결과 화면 + 기록 저장]
```

### 상태 전이

```
draft ──→ in_progress ──→ completed
  │
  └──→ cancelled
       (in_progress에서도 취소 가능)
```

---

## 2. 데이터베이스 스키마

### 2.1 `rounds` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | - |
| user_id | bigint (FK→users) | O | 라운드 생성자 |
| course_id | bigint (FK→parkgolf) | X | 서버 등록된 코스 (null = 미등록 코스) |
| course_name | varchar(255) | O | 코스명 (비정규화) |
| hole_count | tinyint | O | 9 또는 18 |
| hole_pars | json | O | 각 홀 파 배열 `[3,3,4,3,3,3,4,3,3]` |
| status | enum | O | `draft`, `in_progress`, `completed`, `cancelled` |
| memo | text | X | 라운드 메모 |
| played_at | date | O | 플레이 날짜 |
| started_at | timestamp | X | 라운드 시작 시각 |
| completed_at | timestamp | X | 라운드 완료 시각 |
| created_at | timestamp | auto | - |
| updated_at | timestamp | auto | - |

**인덱스:**
- `(user_id, status)` — 내 진행 중 라운드 조회
- `(user_id, played_at)` — 내 라운드 기록 날짜순
- `(course_id)` — 코스별 라운드 조회

### 2.2 `round_players` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | Flutter에서 `playerId`로 사용 |
| round_id | bigint (FK→rounds) | O | CASCADE DELETE |
| user_id | bigint (FK→users) | X | null = 게스트 (앱 미가입자) |
| player_name | varchar(100) | O | 표시 이름 |
| player_order | tinyint | O | 순서 1~4 |
| is_me | boolean | O | 라운드 생성자 본인 여부 |
| total_score | int | O | 캐시된 총 타수 (default: 0) |
| score_vs_par | int | O | 캐시된 파 대비 (default: 0) |
| rank | tinyint | X | 최종 순위 (완료 시 계산) |
| is_winner | boolean | O | 우승 여부 (default: false) |
| created_at | timestamp | auto | - |
| updated_at | timestamp | auto | - |

**인덱스:**
- `(round_id)` — 라운드별 플레이어 조회
- `(user_id)` — 사용자별 참가 라운드

### 2.3 `round_scores` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | - |
| round_id | bigint (FK→rounds) | O | CASCADE DELETE |
| round_player_id | bigint (FK→round_players) | O | CASCADE DELETE |
| hole_number | tinyint | O | 1~18 |
| par | tinyint | O | 해당 홀 파 (3 또는 4) |
| score | tinyint | X | 실제 타수 (null = 미입력) |
| memo | varchar(255) | X | 홀 메모 |
| recorded_at | timestamp | X | 스코어 기록 시각 |
| created_at | timestamp | auto | - |
| updated_at | timestamp | auto | - |

**인덱스:**
- `UNIQUE (round_id, round_player_id, hole_number)` — 중복 방지
- `(round_player_id)` — 플레이어별 스코어 조회

---

## 3. API 엔드포인트 상세

### 3.1 라운드 생성 — `POST /api/rounds`

**인증:** Sanctum (필수)

**Request:**
```json
{
  "course_id": 123,
  "course_name": "○○ 파크골프장",
  "hole_count": 9,
  "hole_pars": [3, 3, 4, 3, 3, 3, 4, 3, 3],
  "played_at": "2026-02-10",
  "memo": "날씨 좋음",
  "players": [
    { "name": "나", "is_me": true, "user_id": null },
    { "name": "김철수", "is_me": false, "user_id": null },
    { "name": "박영희", "is_me": false, "user_id": 45 }
  ]
}
```

**검증 규칙:**
| 필드 | 규칙 |
|------|------|
| course_id | nullable, exists:parkgolf,id |
| course_name | required, max:255 |
| hole_count | required, in:9,18 |
| hole_pars | required, array, 길이=hole_count, 각 값 in:3,4 |
| played_at | required, date, before_or_equal:today |
| memo | nullable, max:1000 |
| players | required, array, min:1, max:4 |
| players.*.name | required, max:100 |
| players.*.is_me | required, boolean |
| players.*.user_id | nullable, exists:users,id |

**추가 검증:**
- `is_me=true`인 플레이어가 정확히 **1명** 존재해야 함
- `hole_pars`가 전달되지 않으면 기본값: 전부 `3`

**서버 처리:**
1. `rounds` 레코드 생성 (status=`draft`)
2. `players` 배열 순회하며 `round_players` 생성 (player_order = 배열 인덱스 + 1)
3. 각 플레이어 × 각 홀에 대해 `round_scores` 생성 (score=`null`, par=해당홀 파)
4. 생성된 전체 라운드 데이터 반환

**Response: `201 Created`**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 10,
    "course_id": 123,
    "course_name": "○○ 파크골프장",
    "hole_count": 9,
    "hole_pars": [3, 3, 4, 3, 3, 3, 4, 3, 3],
    "status": "draft",
    "memo": "날씨 좋음",
    "played_at": "2026-02-10",
    "started_at": null,
    "completed_at": null,
    "created_at": "2026-02-10T09:00:00Z",
    "updated_at": "2026-02-10T09:00:00Z",
    "players": [
      {
        "id": 1,
        "player_name": "나",
        "player_order": 1,
        "is_me": true,
        "user_id": null,
        "total_score": 0,
        "score_vs_par": 0,
        "rank": null,
        "is_winner": false,
        "scores": [
          { "hole_number": 1, "par": 3, "score": null, "memo": null },
          { "hole_number": 2, "par": 3, "score": null, "memo": null },
          ...
        ]
      },
      ...
    ]
  }
}
```

---

### 3.2 라운드 시작 — `POST /api/rounds/{id}/start`

**인증:** Sanctum (라운드 생성자만)

**Request:** 없음 (빈 body)

**검증:**
- 라운드 소유자(user_id) 확인
- status == `draft` 확인

**서버 처리:**
1. status → `in_progress`
2. started_at = `now()`

**Response: `200 OK`**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "in_progress",
    "started_at": "2026-02-10T09:05:00Z",
    ...
  }
}
```

---

### 3.3 스코어 입력 — `POST /api/rounds/{id}/scores`

**인증:** Sanctum (라운드 생성자만)

**Request:**
```json
{
  "player_id": 1,
  "hole_number": 3,
  "score": 4,
  "memo": "벙커 빠짐"
}
```

**검증 규칙:**
| 필드 | 규칙 |
|------|------|
| player_id | required, exists:round_players,id (해당 라운드 소속) |
| hole_number | required, integer, 1~hole_count |
| score | required, integer, 1~20 |
| memo | nullable, max:255 |

**서버 처리:**
1. `round_scores` 에서 (round_player_id, hole_number) 찾기
2. score, memo, recorded_at 업데이트
3. `round_players` 캐시 재계산:
   ```sql
   total_score = SUM(score) WHERE score IS NOT NULL
   score_vs_par = total_score - SUM(par) WHERE score IS NOT NULL
   ```
4. 업데이트된 스코어 반환

**Response: `200 OK`**
```json
{
  "success": true,
  "data": {
    "hole_number": 3,
    "par": 4,
    "score": 4,
    "memo": "벙커 빠짐",
    "recorded_at": "2026-02-10T09:25:00Z"
  },
  "player_summary": {
    "player_id": 1,
    "total_score": 10,
    "score_vs_par": 1,
    "holes_completed": 3
  }
}
```

---

### 3.4 스코어 수정 — `PUT /api/rounds/{id}/scores/{holeNumber}`

**인증:** Sanctum (라운드 생성자만)

**Request:**
```json
{
  "player_id": 1,
  "score": 3,
  "memo": "수정"
}
```

> 3.3과 동일한 로직. 이미 입력된 스코어를 수정하는 경우.
> 실질적으로 3.3의 POST도 upsert 동작이므로, PUT은 명시적 수정 의도.

---

### 3.5 라운드 완료 — `POST /api/rounds/{id}/complete`

**인증:** Sanctum (라운드 생성자만)

**Request:** 없음

**검증:**
- status == `in_progress`
- 모든 플레이어의 모든 홀 스코어 입력 완료

**서버 처리:**
1. 미입력 스코어 확인 → 있으면 `422 에러`
2. 각 플레이어 최종 `total_score`, `score_vs_par` 확정
3. **순위 계산:**
   ```
   1) total_score 오름차순 정렬
   2) 동점 시: 뒷홀부터 역순 비교 (9홀→8홀→7홀...)
      - 뒷홀 스코어가 낮은 사람이 상위
   3) 여전히 동점: 동일 순위 부여
   ```
4. 1위 `is_winner = true` (동일 순위 1위 시 모두 true)
5. status → `completed`, completed_at = `now()`

**Response: `200 OK`**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course_name": "○○ 파크골프장",
    "played_at": "2026-02-10",
    "hole_count": 9,
    "hole_pars": [3, 3, 4, 3, 3, 3, 4, 3, 3],
    "total_par": 30,
    "players": [
      {
        "player_id": 1,
        "player_name": "나",
        "scores": [3, 2, 4, 3, 3, 2, 4, 3, 3],
        "total_score": 27,
        "score_vs_par": -3,
        "rank": 1,
        "is_winner": true
      },
      {
        "player_id": 2,
        "player_name": "김철수",
        "scores": [3, 3, 4, 4, 3, 3, 4, 3, 3],
        "total_score": 30,
        "score_vs_par": 0,
        "rank": 2,
        "is_winner": false
      }
    ]
  }
}
```

---

### 3.6 라운드 취소 — `DELETE /api/rounds/{id}`

**인증:** Sanctum (라운드 생성자만)

**검증:**
- status가 `completed`가 **아닌** 경우만 취소 가능
- 완료된 라운드는 취소 불가 (기록으로 남아야 함)

**서버 처리:**
1. status → `cancelled`
2. Soft delete (실제 삭제하지 않음)

**Response: `200 OK`**
```json
{
  "success": true,
  "message": "라운드가 취소되었습니다."
}
```

---

### 3.7 라운드 상세 — `GET /api/rounds/{id}`

**인증:** Sanctum

**Response:** 3.1의 Response와 동일한 구조

---

### 3.8 라운드 목록 — `GET /api/rounds`

**인증:** Sanctum

**Query Parameters:**
| 파라미터 | 타입 | 기본값 | 설명 |
|---------|------|--------|------|
| status | string | - | 필터: draft, in_progress, completed |
| page | int | 1 | 페이지 번호 |
| per_page | int | 20 | 페이지 크기 (max: 50) |

**Response: `200 OK`** (Laravel 페이지네이션)
```json
{
  "data": [ ... ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 42
}
```

---

### 3.9 스코어카드 조회 — `GET /api/rounds/{id}/scorecard`

**인증:** Sanctum

**Response: `200 OK`**
```json
{
  "success": true,
  "data": {
    "round_id": 1,
    "course_name": "○○ 파크골프장",
    "hole_count": 9,
    "hole_pars": [3, 3, 4, 3, 3, 3, 4, 3, 3],
    "total_par": 30,
    "scorecards": [
      {
        "player_id": 1,
        "player_name": "나",
        "scores": [
          { "hole_number": 1, "par": 3, "score": 3, "memo": null },
          { "hole_number": 2, "par": 3, "score": 2, "memo": "버디!" },
          ...
        ],
        "total_score": 27,
        "score_vs_par": -3
      }
    ]
  }
}
```

---

## 4. 기록(Record) 연동

라운드가 `completed` 되면 자동으로 기록/통계에 반영됩니다.

### `GET /api/records/statistics` 구현 방식

서버에서 `rounds` + `round_players` 테이블을 기반으로 집계:

```sql
-- 기본 통계 (is_me=true인 플레이어 기준)
SELECT
  COUNT(DISTINCT r.id) as total_rounds,
  AVG(rp.total_score) as average_score,
  MIN(rp.total_score) as best_score,
  MAX(rp.total_score) as worst_score,
  SUM(r.hole_count) as total_holes_played,
  SUM(CASE WHEN rp.rank = 1 THEN 1 ELSE 0 END) as win_count
FROM rounds r
JOIN round_players rp ON r.id = rp.round_id AND rp.is_me = true
WHERE r.user_id = ? AND r.status = 'completed'
```

```sql
-- 스코어 타입별 집계
SELECT
  SUM(CASE WHEN rs.score - rs.par <= -2 THEN 1 ELSE 0 END) as eagle_or_better,
  SUM(CASE WHEN rs.score - rs.par = -1 THEN 1 ELSE 0 END) as birdie,
  SUM(CASE WHEN rs.score - rs.par = 0 THEN 1 ELSE 0 END) as par,
  SUM(CASE WHEN rs.score - rs.par = 1 THEN 1 ELSE 0 END) as bogey,
  SUM(CASE WHEN rs.score - rs.par >= 2 THEN 1 ELSE 0 END) as double_or_worse
FROM round_scores rs
JOIN round_players rp ON rs.round_player_id = rp.id AND rp.is_me = true
JOIN rounds r ON rs.round_id = r.id
WHERE r.user_id = ? AND r.status = 'completed' AND rs.score IS NOT NULL
```

### `GET /api/records/rounds` 구현 방식

```sql
SELECT
  r.id,
  r.course_id,
  r.course_name,
  r.played_at as date,
  rp.total_score,
  rp.score_vs_par,
  rp.rank,
  (SELECT COUNT(*) FROM round_players WHERE round_id = r.id) as player_count
FROM rounds r
JOIN round_players rp ON r.id = rp.round_id AND rp.is_me = true
WHERE r.user_id = ? AND r.status = 'completed'
ORDER BY r.played_at DESC
```

---

## 5. Flutter ↔ Server 필드 매핑

### 5.1 타입 변환

| Server | Flutter | 변환 |
|--------|---------|------|
| `id` (int) | `id` (String) | `.toString()` |
| `played_at` (date) | `date` (DateTime) | DateTime.parse() |
| `hole_pars` (json) | `holePars` (List<int>) | 자동 변환 |
| `player_name` | `playerName` | snake→camel |
| `is_me` | `isMe` | snake→camel |
| `total_score` | `totalScore` | snake→camel |

### 5.2 Flutter 모델 수정 필요 사항

**CreateRoundRequest** — players 상세 정보 추가:
```dart
// 현재: playerIds: ["player1", "player2"]
// 변경 필요:
@freezed
class CreateRoundRequest with _$CreateRoundRequest {
  const factory CreateRoundRequest({
    required int? courseId,        // int로 변경 (서버 PK)
    required String courseName,   // 추가
    @Default(9) int holeCount,    // 추가
    List<int>? holePars,          // 추가 (null이면 전부 파3)
    required DateTime date,
    required List<CreatePlayerRequest> players,  // 변경
    String? memo,
  }) = _CreateRoundRequest;
}

@freezed
class CreatePlayerRequest with _$CreatePlayerRequest {
  const factory CreatePlayerRequest({
    required String name,
    @Default(false) bool isMe,
    int? userId,
  }) = _CreatePlayerRequest;
}
```

---

## 6. 에러 처리

| 상황 | HTTP Code | 메시지 |
|------|-----------|--------|
| 미인증 | 401 | "로그인이 필요합니다." |
| 권한 없음 (다른 사용자 라운드) | 403 | "권한이 없습니다." |
| 라운드 없음 | 404 | "라운드를 찾을 수 없습니다." |
| 잘못된 상태 전이 (draft→completed) | 422 | "라운드를 먼저 시작해주세요." |
| 미입력 스코어 있는데 완료 시도 | 422 | "모든 홀의 스코어를 입력해주세요." |
| 플레이어 수 초과 | 422 | "플레이어는 최대 4명입니다." |
| 유효성 검사 실패 | 422 | errors 객체 포함 |

---

## 7. 구현 우선순위

```
Phase 1 (MVP):
  ✅ POST /api/rounds (생성)
  ✅ POST /api/rounds/{id}/start (시작)
  ✅ POST /api/rounds/{id}/scores (스코어 입력)
  ✅ POST /api/rounds/{id}/complete (완료)
  ✅ GET  /api/rounds/{id} (상세)

Phase 2 (기록):
  ✅ GET  /api/rounds (목록)
  ✅ GET  /api/rounds/{id}/scorecard (스코어카드)
  ✅ GET  /api/records/statistics (통계)
  ✅ GET  /api/records/rounds (기록 목록)

Phase 3 (개선):
  - DELETE /api/rounds/{id} (취소)
  - PUT /api/rounds/{id}/scores/{hole} (스코어 수정)
  - GET /api/records/monthly (월별 요약)
  - GET /api/records/courses (코스별 기록)
```

---

## 8. Laravel 마이그레이션 예시

```php
// database/migrations/xxxx_create_rounds_table.php
Schema::create('rounds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('course_id')->nullable()->constrained('parkgolf')->nullOnDelete();
    $table->string('course_name');
    $table->tinyInteger('hole_count')->default(9);
    $table->json('hole_pars');
    $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
    $table->text('memo')->nullable();
    $table->date('played_at');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index(['user_id', 'played_at']);
    $table->index('course_id');
});

// database/migrations/xxxx_create_round_players_table.php
Schema::create('round_players', function (Blueprint $table) {
    $table->id();
    $table->foreignId('round_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('player_name', 100);
    $table->tinyInteger('player_order');
    $table->boolean('is_me')->default(false);
    $table->integer('total_score')->default(0);
    $table->integer('score_vs_par')->default(0);
    $table->tinyInteger('rank')->nullable();
    $table->boolean('is_winner')->default(false);
    $table->timestamps();

    $table->index('round_id');
    $table->index('user_id');
});

// database/migrations/xxxx_create_round_scores_table.php
Schema::create('round_scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('round_id')->constrained()->cascadeOnDelete();
    $table->foreignId('round_player_id')->constrained()->cascadeOnDelete();
    $table->tinyInteger('hole_number');
    $table->tinyInteger('par');
    $table->tinyInteger('score')->nullable();
    $table->string('memo', 255)->nullable();
    $table->timestamp('recorded_at')->nullable();
    $table->timestamps();

    $table->unique(['round_id', 'round_player_id', 'hole_number'], 'round_score_unique');
    $table->index('round_player_id');
});
```
