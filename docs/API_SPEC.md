# Park Golf 서버 API 구현 요청서

> 작성일: 2026-02-11
> 앱: Flutter (GetX + GoRouter + Dio/Retrofit + Freezed)
> 서버: Laravel 12 + Sanctum
> 기존 구현 완료: `/api/parkgolf/*`, `/api/profile/*`, `/api/auth/*`

---

## 목차

1. [코스 검색 API 수정](#1-코스-검색-api-수정)
2. [라운드 API (신규)](#2-라운드-api-신규)
3. [기록/통계 API (신규)](#3-기록통계-api-신규)
4. [홈 화면 API (신규)](#4-홈-화면-api-신규)
5. [뉴스 API (신규 - 기존 스크래핑 데이터 조회)](#5-뉴스-api-신규)
6. [카카오톡 공유 웹 페이지](#6-카카오톡-공유-웹-페이지)
7. [DB 스키마](#7-db-스키마)
8. [공통 규칙](#8-공통-규칙)
9. [구현 우선순위](#9-구현-우선순위)

---

## 1. 코스 검색 API 수정

### `GET /api/parkgolf/search` (기존 API 확장)

기존 검색 API에 위치 파라미터를 **선택사항**으로 추가합니다.

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| name | string | X | 코스명/주소 검색어 |
| region | string | X | 지역 필터 |
| lat | float | X | 위도 (-90 ~ 90) |
| lon | float | X | 경도 (-180 ~ 180) |
| radius | int | X | 검색 반경 km (기본: 10, 최대: 50) |
| sort | string | X | `relevance`(기본) / `distance` / `rating` |
| per_page | int | X | 페이지당 개수 (기본: 20, 최대: 100) |
| page | int | X | 페이지 번호 (기본: 1) |

**정렬 규칙:**
- `lat`/`lon` 없음 → 기존 동작 유지 (`relevance`)
- `lat`/`lon` 있음 + `sort` 미지정 → 자동 `distance`
- `sort=distance` + `lat`/`lon` 없음 → 422 에러

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "서울파크골프장",
      "address": "서울시 강남구 ...",
      "region": "서울",
      "lat": 37.123456,
      "lon": 127.123456,
      "hole_count": 18,
      "par": 66,
      "distance_km": 2.3,
      "rating": 4.5,
      "thumbnail": "https://..."
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 45,
  "last_page": 3
}
```

> `distance_km`은 `lat`/`lon` 제공 시에만 포함.

**성능 참고:** Haversine 계산 전 bounding box 선필터링 권장

---

## 2. 라운드 API (신규)

### 전체 흐름

```
[앱] 코스 선택 + 플레이어 설정
  ↓
[서버] POST /api/rounds → 라운드 생성 (status: in_progress)
  ↓
[앱] 매 홀 스코어 입력 → 로컬(sqflite)에 저장 (서버 미연동)
  ↓
[서버] POST /api/rounds/{id}/complete → 전체 스코어 일괄 전송
  ↓
[서버] 순위 계산 + 통계 갱신 → 결과 반환
```

> **핵심 결정**: 스코어는 앱 로컬에 저장하다가, 라운딩 완료 시 서버에 한번에 전송합니다.
> 서버 요청은 라운드당 **2번** (생성 1번 + 완료 1번).

### 상태 전이

```
in_progress ──→ completed
     │
     └──→ cancelled
```

---

### 2-1. 라운드 생성

### `POST /api/parkgolf/rounds`

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
    { "name": "홍길동", "is_me": true, "user_id": null },
    { "name": "김철수", "is_me": false, "user_id": null },
    { "name": "박영희", "is_me": false, "user_id": 45 }
  ]
}
```

**필드 규칙:**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| course_id | int | X | nullable. 코스 미선택 시 null |
| course_name | string | O | 코스명 (미선택 시 "직접 입력" 등) |
| hole_count | int | O | 9 또는 18 |
| hole_pars | array[int] | O | 각 홀 파 배열. 길이 = hole_count. 값: 3~5 |
| played_at | date | O | 플레이 날짜 (today 이전) |
| memo | string | X | 라운드 메모 (최대 1000자) |
| players | array | O | 최소 1명, 최대 6명 |
| players[].name | string | O | 이름 (최대 100자) |
| players[].is_me | bool | O | 본인 여부 (**정확히 1명만 true**) |
| players[].user_id | int | X | 앱 가입 유저면 user_id, 게스트면 null |

**Validation 규칙:**
- `course_id`가 있으면 → 서버에서 해당 코스의 홀/파 정보로 덮어쓰기 가능
- `course_id`가 null이면 → `hole_pars` 배열 필수
- `is_me: true`인 플레이어가 정확히 1명
- `is_me: true`인 플레이어의 `name`은 로그인 유저 닉네임으로 서버에서 덮어쓰기

**서버 처리:**
1. `rounds` 생성 (status = `in_progress`, started_at = now())
2. `round_players` 생성 (player_order = 배열 인덱스 + 1)
3. 각 플레이어 × 각 홀에 대해 `round_scores` 생성 (score = null)
4. 생성된 전체 데이터 반환

**Response (201):**
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
    "status": "in_progress",
    "memo": "날씨 좋음",
    "played_at": "2026-02-10",
    "started_at": "2026-02-10T09:00:00Z",
    "completed_at": null,
    "players": [
      {
        "id": 1,
        "player_name": "홍길동",
        "player_order": 1,
        "is_me": true,
        "user_id": null,
        "total_score": 0,
        "score_vs_par": 0,
        "rank": null,
        "is_winner": false
      }
    ]
  }
}
```

> `players[].id`는 서버에서 발급한 PK. 이후 스코어 전송 시 이 ID를 사용합니다.

---

### 2-2. 라운드 완료 (스코어 일괄 전송)

### `POST /api/parkgolf/rounds/{id}/complete`

**인증:** Sanctum (라운드 생성자만)

**Request:**
```json
{
  "scores": [
    {
      "player_id": 1,
      "hole_scores": [
        { "hole_number": 1, "score": 3, "memo": null },
        { "hole_number": 2, "score": 2, "memo": "버디!" },
        { "hole_number": 3, "score": 4, "memo": "벙커 빠짐" },
        { "hole_number": 4, "score": 3, "memo": null },
        { "hole_number": 5, "score": 3, "memo": null },
        { "hole_number": 6, "score": 2, "memo": null },
        { "hole_number": 7, "score": 4, "memo": null },
        { "hole_number": 8, "score": 3, "memo": null },
        { "hole_number": 9, "score": 3, "memo": null }
      ]
    },
    {
      "player_id": 2,
      "hole_scores": [
        { "hole_number": 1, "score": 3, "memo": null },
        { "hole_number": 2, "score": 3, "memo": null },
        ...
      ]
    }
  ]
}
```

**필드 규칙:**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| scores | array | O | 플레이어별 스코어 배열 |
| scores[].player_id | int | O | 라운드 생성 시 발급된 플레이어 ID |
| scores[].hole_scores | array | O | 홀별 스코어 |
| scores[].hole_scores[].hole_number | int | O | 홀 번호 (1 ~ hole_count) |
| scores[].hole_scores[].score | int | O | 타수 (1~20) |
| scores[].hole_scores[].memo | string | X | 홀 메모 (최대 255자) |

**Validation 규칙:**
- status == `in_progress` 확인 → 아니면 409
- 모든 `player_id`가 해당 라운드에 존재하는 플레이어여야 함
- 모든 플레이어의 스코어가 포함되어야 함 (누락 불가)
- 각 플레이어의 `hole_scores` 수 = 라운드의 `hole_count`
- 홀 번호 중복 불가

**서버 처리:**
1. `round_scores` 일괄 업데이트 (score, memo, recorded_at)
2. 각 플레이어 `total_score`, `score_vs_par` 계산
3. **순위 계산:**
   ```
   1) total_score 오름차순 정렬
   2) 동점 시: 뒷홀부터 역순 비교 (9홀→8홀→7홀...)
      → 뒷홀 스코어가 낮은 사람이 상위
   3) 여전히 동점: 동일 순위 부여
   ```
4. 1위 `is_winner = true` (공동 1위 시 모두 true)
5. status → `completed`, completed_at = now()

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course_name": "○○ 파크골프장",
    "hole_count": 9,
    "hole_pars": [3, 3, 4, 3, 3, 3, 4, 3, 3],
    "total_par": 30,
    "played_at": "2026-02-10",
    "started_at": "2026-02-10T09:00:00Z",
    "completed_at": "2026-02-10T11:30:00Z",
    "players": [
      {
        "player_id": 1,
        "player_name": "홍길동",
        "is_me": true,
        "scores": [3, 2, 4, 3, 3, 2, 4, 3, 3],
        "total_score": 27,
        "score_vs_par": -3,
        "rank": 1,
        "is_winner": true
      },
      {
        "player_id": 2,
        "player_name": "김철수",
        "is_me": false,
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

**에러:**
- `409 Conflict`: 이미 completed/cancelled 상태
- `422 Unprocessable`: 스코어 누락/중복/범위 초과

---

### 2-3. 라운드 상세 조회

### `GET /api/parkgolf/rounds/{id}`

**인증:** Sanctum

**Response (200):**
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
    "total_par": 30,
    "status": "completed",
    "memo": "날씨 좋음",
    "played_at": "2026-02-10",
    "started_at": "2026-02-10T09:00:00Z",
    "completed_at": "2026-02-10T11:30:00Z",
    "players": [
      {
        "id": 1,
        "player_name": "홍길동",
        "player_order": 1,
        "is_me": true,
        "total_score": 27,
        "score_vs_par": -3,
        "rank": 1,
        "is_winner": true,
        "scores": [
          { "hole_number": 1, "par": 3, "score": 3, "memo": null },
          { "hole_number": 2, "par": 3, "score": 2, "memo": "버디!" }
        ]
      }
    ]
  }
}
```

---

### 2-4. 스코어카드 조회

### `GET /api/parkgolf/rounds/{id}/scorecard`

**인증:** Sanctum

> 2-3과 동일한 데이터지만, 스코어카드 UI에 최적화된 형태로 반환.

**Response (200):**
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
        "player_name": "홍길동",
        "scores": [
          { "hole_number": 1, "par": 3, "score": 3, "memo": null },
          { "hole_number": 2, "par": 3, "score": 2, "memo": "버디!" }
        ],
        "total_score": 27,
        "score_vs_par": -3
      }
    ]
  }
}
```

---

### 2-5. 내 라운드 목록

### `GET /api/parkgolf/rounds`

**인증:** Sanctum

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| status | string | X | `in_progress`, `completed` (기본: 전체) |
| page | int | X | 페이지 번호 (기본: 1) |
| per_page | int | X | 페이지당 개수 (기본: 20, 최대: 50) |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_name": "○○ 파크골프장",
      "hole_count": 9,
      "status": "completed",
      "played_at": "2026-02-10",
      "total_score": 27,
      "score_vs_par": -3,
      "rank": 1,
      "player_count": 3,
      "started_at": "2026-02-10T09:00:00Z",
      "completed_at": "2026-02-10T11:30:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 42,
  "last_page": 3
}
```

> `total_score`, `score_vs_par`, `rank`은 `is_me=true`인 플레이어 기준.

---

### 2-6. 라운드 취소

### `DELETE /api/parkgolf/rounds/{id}`

**인증:** Sanctum (라운드 생성자만)

**규칙:**
- `completed` 상태가 **아닌** 경우만 취소 가능
- Soft delete (status → `cancelled`)

**Response (200):**
```json
{
  "success": true,
  "message": "라운드가 취소되었습니다."
}
```

---

## 3. 기록/통계 API (신규)

### 3-1. 통계 요약

### `GET /api/parkgolf/records/statistics`

**인증:** Sanctum

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| year | int | X | 연도 (기본: 올해) |
| month | int | X | 월 (미지정 시 연간 통계) |

**서버 쿼리 참고:**
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

-- 스코어 분포
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

**Response (200):**
```json
{
  "success": true,
  "data": {
    "period": "2026-02",
    "total_rounds": 8,
    "average_score": 78.5,
    "best_score": 72,
    "worst_score": 85,
    "total_holes_played": 144,
    "win_count": 3,
    "par_distribution": {
      "eagle_or_better": 0,
      "birdie": 12,
      "par": 45,
      "bogey": 28,
      "double_or_worse": 15
    }
  }
}
```

---

### 3-2. 라운드 기록 목록

### `GET /api/parkgolf/records/rounds`

**인증:** Sanctum

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| year | int | X | 연도 필터 |
| month | int | X | 월 필터 |
| course_id | int | X | 코스 필터 |
| page | int | X | 페이지 번호 (기본: 1) |
| per_page | int | X | 페이지당 개수 (기본: 20) |

**서버 쿼리 참고:**
```sql
SELECT
  r.id, r.course_id, r.course_name,
  r.played_at as date, r.hole_count,
  rp.total_score, rp.score_vs_par, rp.rank,
  (SELECT COUNT(*) FROM round_players WHERE round_id = r.id) as player_count
FROM rounds r
JOIN round_players rp ON r.id = rp.round_id AND rp.is_me = true
WHERE r.user_id = ? AND r.status = 'completed'
ORDER BY r.played_at DESC
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2026-02-10",
      "course_id": 123,
      "course_name": "○○ 파크골프장",
      "hole_count": 9,
      "total_score": 27,
      "score_vs_par": -3,
      "rank": 1,
      "player_count": 3
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 24,
  "last_page": 2
}
```

---

### 3-3. 월별 요약

### `GET /api/parkgolf/records/monthly`

**인증:** Sanctum

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| year | int | O | 연도 |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "year": 2026,
      "month": 1,
      "rounds_count": 12,
      "avg_score": 80.2,
      "best_score": 72
    },
    {
      "year": 2026,
      "month": 2,
      "rounds_count": 8,
      "avg_score": 77.8,
      "best_score": 70
    }
  ]
}
```

---

## 4. 홈 화면 API (신규)

### `GET /api/parkgolf/home`

**인증:** Sanctum

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| lat | float | X | 위도 (주변 코스 추천용) |
| lon | float | X | 경도 |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user_summary": {
      "total_rounds": 24,
      "this_month_rounds": 8,
      "avg_score": 78.5,
      "best_score": 72
    },
    "in_progress_round": {
      "id": 125,
      "course_name": "○○ 파크골프장",
      "started_at": "2026-02-11T07:30:00Z",
      "player_count": 3
    },
    "recent_rounds": [
      {
        "id": 123,
        "course_name": "○○ 파크골프장",
        "total_score": 27,
        "score_vs_par": -3,
        "played_at": "2026-02-10",
        "player_count": 3
      }
    ]
  }
}
```

> `in_progress_round`: 진행 중인 라운드가 있으면 포함, 없으면 null.

---

## 5. 뉴스 API (신규)

### `GET /api/news`

서버에 이미 스크래핑된 뉴스 데이터를 조회합니다.

**인증:** 불필요 (또는 선택)

**Request Parameters:**

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| page | int | X | 페이지 번호 (기본: 1) |
| per_page | int | X | 페이지당 개수 (기본: 20) |
| category | string | X | 서버에서 지원하는 카테고리 필터 |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "제15회 전국 파크골프 대회 개최",
      "summary": "오는 3월 15일 서울 올림픽공원에서...",
      "source": "스포츠서울",
      "source_url": "https://original-article-url.com/123",
      "thumbnail": "https://...",
      "category": "news",
      "published_at": "2026-02-10T09:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 150,
  "last_page": 8
}
```

> 기존 스크래핑 DB 스키마에 맞춰 응답 필드를 조정해주세요.
> 앱에서는 `source_url`로 WebView 원문 이동합니다.

---

## 6. 카카오톡 공유 웹 페이지

### `GET /rounds/{roundId}` (웹 라우트, API 아님)

카카오톡 공유 링크 클릭 시 보여줄 웹 페이지.

**OG 메타태그:**
```html
<meta property="og:title" content="홍길동님의 파크골프 라운드" />
<meta property="og:description" content="○○파크골프장 9홀 | 27타 (파 -3) | 2026.02.10" />
<meta property="og:image" content="https://parkgolf.app/og-images/rounds/1.png" />
<meta property="og:url" content="https://parkgolf.app/rounds/1" />
```

**페이지 내용:**
- 라운드 결과 요약 (코스명, 날짜, 참여자, 스코어)
- 앱 다운로드 유도 배너
- 앱 미설치자도 결과 확인 가능

---

## 7. DB 스키마

### 7-1. `rounds` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | - |
| user_id | bigint (FK→users) | O | 라운드 생성자 |
| course_id | bigint (FK→parkgolf) | X | null = 미등록 코스 |
| course_name | varchar(255) | O | 코스명 (비정규화) |
| hole_count | tinyint | O | 9 또는 18 |
| hole_pars | json | O | 각 홀 파 배열 `[3,3,4,3,3,3,4,3,3]` |
| status | enum | O | `in_progress`, `completed`, `cancelled` |
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

### 7-2. `round_players` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | Flutter에서 `playerId`로 사용 |
| round_id | bigint (FK→rounds) | O | CASCADE DELETE |
| user_id | bigint (FK→users) | X | null = 게스트 |
| player_name | varchar(100) | O | 표시 이름 |
| player_order | tinyint | O | 순서 1~6 |
| is_me | boolean | O | 라운드 생성자 본인 여부 |
| total_score | int | O | 캐시된 총 타수 (default: 0) |
| score_vs_par | int | O | 캐시된 파 대비 (default: 0) |
| rank | tinyint | X | 최종 순위 (완료 시 계산) |
| is_winner | boolean | O | 우승 여부 (default: false) |
| created_at | timestamp | auto | - |
| updated_at | timestamp | auto | - |

**인덱스:**
- `(round_id)`
- `(user_id)`

### 7-3. `round_scores` 테이블

| 컬럼 | 타입 | 필수 | 설명 |
|------|------|------|------|
| id | bigint (PK) | auto | - |
| round_id | bigint (FK→rounds) | O | CASCADE DELETE |
| round_player_id | bigint (FK→round_players) | O | CASCADE DELETE |
| hole_number | tinyint | O | 1~18 |
| par | tinyint | O | 해당 홀 파 |
| score | tinyint | X | 실제 타수 (null = 미입력) |
| memo | varchar(255) | X | 홀 메모 |
| recorded_at | timestamp | X | 스코어 기록 시각 |
| created_at | timestamp | auto | - |
| updated_at | timestamp | auto | - |

**인덱스:**
- `UNIQUE (round_id, round_player_id, hole_number)`
- `(round_player_id)`

### 7-4. Laravel 마이그레이션

```php
// database/migrations/xxxx_create_rounds_table.php
Schema::create('rounds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('course_id')->nullable()->constrained('parkgolf')->nullOnDelete();
    $table->string('course_name');
    $table->tinyInteger('hole_count')->default(9);
    $table->json('hole_pars');
    $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
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

---

## 8. 공통 규칙

### 8-1. 인증
- Round, Record, Home API(`/api/parkgolf/rounds`, `/api/parkgolf/records`, `/api/parkgolf/home`)는 모두 Sanctum 토큰 필수
- 코스 검색 API(`/api/parkgolf/search`, `/api/parkgolf/nearby` 등)는 인증 불필요
- 뉴스 API는 인증 선택적
- `401 Unauthorized` → 앱에서 강제 로그아웃
- Refresh token 없음

### 8-2. 응답 형식

**성공:**
```json
{
  "success": true,
  "data": { ... },
  "message": "선택적 메시지"
}
```

**목록 (페이지네이션):**
```json
{
  "success": true,
  "data": [ ... ],
  "current_page": 1,
  "per_page": 20,
  "total": 42,
  "last_page": 3
}
```

**에러:**
```json
{
  "success": false,
  "message": "에러 메시지",
  "errors": {
    "field_name": ["상세 에러 메시지"]
  }
}
```

### 8-3. HTTP Status Code

| Code | 설명 |
|------|------|
| 200 | 성공 |
| 201 | 생성 성공 |
| 401 | 인증 필요 |
| 403 | 권한 없음 (다른 사용자의 라운드) |
| 404 | 리소스 없음 |
| 409 | 충돌 (이미 완료된 라운드) |
| 422 | 유효성 검증 실패 |
| 429 | 요청 제한 초과 |

### 8-4. Flutter ↔ Server 필드 매핑

| Server (snake_case) | Flutter (camelCase) | 변환 |
|---------------------|--------------------|----|
| player_name | playerName | 자동 |
| is_me | isMe | 자동 |
| total_score | totalScore | 자동 |
| hole_pars | holePars | 자동 |
| played_at | playedAt | DateTime.parse() |
| score_vs_par | scoreVsPar | 자동 |
| id (int) | id (String) | .toString() |

---

## 9. 구현 우선순위

### P0 — MVP 필수 (런칭 전)
```
1. POST /api/parkgolf/rounds              라운드 생성
2. POST /api/parkgolf/rounds/{id}/complete 라운드 완료 (스코어 일괄 전송)
3. GET  /api/parkgolf/rounds/{id}         라운드 상세
4. GET  /api/parkgolf/rounds              내 라운드 목록
5. DELETE /api/parkgolf/rounds/{id}       라운드 취소
6. GET /api/parkgolf/search 수정           위치 파라미터 추가
```

### P1 — 1차 업데이트 (런칭 후)
```
7.  GET  /api/parkgolf/home                홈 화면 데이터
8.  GET  /api/parkgolf/records/statistics  통계 요약
9.  GET  /api/parkgolf/records/rounds      기록 목록
10. GET  /api/parkgolf/rounds/{id}/scorecard 스코어카드
11. GET  /api/parkgolf/records/monthly     월별 요약
```

### P2 — 2차 업데이트
```
12. GET  /api/news                뉴스 조회
13. GET  /rounds/{id} (웹)        카카오톡 공유 페이지
```

---

*이 문서에 대한 질문이나 수정 요청은 앱 개발팀에 연락해주세요.*
