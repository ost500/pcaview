# Park Golf - API 연동 레퍼런스

> Flutter 클라이언트 ↔ PCAview 서버 간 API 연동 가이드
>
> **서버:** Laravel 12 (Sanctum 인증) | **클라이언트:** Flutter (Dio + Retrofit)
> **Base URL:** `AppConfig.baseUrl` (현재 placeholder, 실제 URL 설정 필요)

---

## 목차

1. [연동 현황 요약](#연동-현황-요약)
2. [인증 (AuthService)](#1-인증-authservice)
3. [코스 (CourseService)](#2-코스-courseservice)
4. [라운드 (RoundService)](#3-라운드-roundservice)
5. [기록 (RecordService)](#4-기록-recordservice)
6. [클럽 (ClubService)](#5-클럽-clubservice)
7. [서버 전용 API (앱 미사용)](#6-서버-전용-api-앱-미사용)
8. [공통 사항](#7-공통-사항)

---

## 연동 현황 요약

### Flutter Service ↔ Server 매핑 상태

| Flutter Service | 서버 상태 | 비고 |
|----------------|----------|------|
| `AuthService` | **부분 구현** | 로그인/로그아웃 있음, social-login/refresh 경로 다름 |
| `CourseService` | **부분 구현** | 서버 경로 `/api/parkgolf/*`, 클라이언트 기대 `/courses/*` |
| `RoundService` | **미구현** | 서버에 라운드 관련 API 없음 |
| `RecordService` | **미구현** | 서버에 기록/통계 API 없음 |
| `ClubService` | **미구현** | 서버에 클럽 관련 API 없음 |

### 서버 경로 vs 클라이언트 경로 차이

```
서버 실제 경로              Flutter 기대 경로          상태
─────────────────────────────────────────────────────────
POST /api/auth/login     → POST /auth/login          ✅ 매핑 가능
POST /api/auth/logout    → POST /auth/logout         ✅ 매핑 가능
POST /api/auth/kakao/cb  → POST /auth/social-login   ⚠️ 경로/방식 다름
GET  /api/auth/user      → (AuthController에서 사용)  ✅ 활용 가능
없음                     → POST /auth/refresh         ❌ 서버 구현 필요
GET  /api/parkgolf/*     → GET  /courses/*            ⚠️ 경로 조정 필요
없음                     → /rounds/*                  ❌ 서버 구현 필요
없음                     → /records/*                 ❌ 서버 구현 필요
없음                     → /clubs/*                   ❌ 서버 구현 필요
GET  /api/profile        → (미연결)                   💡 활용 가능
POST /api/feed           → (ClubService 피드와 유사)   💡 활용 가능
```

---

## 1. 인증 (AuthService)

> **파일:** `lib/infra/api/auth_service.dart`
> **baseUrl:** `AppConfig.baseUrl + '/auth'` → 서버: `/api/auth`

### 1.1 로그인 — `POST /auth/login`

```
Flutter:  POST /auth/login
서버:     POST /api/auth/login  ✅ 일치
```

**Flutter 호출:**
```dart
final response = await authService.login({
  'email': 'user@example.com',
  'password': 'password123',
  'device_name': 'iPhone 15',  // 서버에서 optional
});
```

**서버 Request:**
```json
{
  "email": "string (required, email 형식)",
  "password": "string (required)",
  "device_name": "string (optional, max:255)"
}
```

**서버 Response:** `200 OK`
```json
{
  "success": true,
  "token": "1|abc123tokenstring...",
  "user": {
    "id": 1,
    "name": "홍길동",
    "email": "user@example.com",
    "profile_photo_url": "https://..."
  }
}
```

> **주의:** 서버는 `success` + `token` + `user` 구조 반환.
> Flutter `ApiResponse<T>` 구조와 다르므로 **AuthRepositoryImpl에서 변환 필요**.

---

### 1.2 소셜 로그인 — `POST /auth/social-login`

```
Flutter:  POST /auth/social-login
서버:     POST /api/auth/kakao/callback  ⚠️ 경로/방식 다름
```

**서버 실제 동작:**
- 서버는 카카오 OAuth 콜백 전용 (`/api/auth/kakao/callback`)
- 범용 소셜 로그인 엔드포인트는 없음

**Flutter 호출 (기대):**
```dart
final response = await authService.socialLogin({
  'provider': 'kakao',
  'access_token': 'kakao_access_token',
  'device_name': 'iPhone 15',
});
```

> **TODO:** 서버에서 범용 소셜 로그인 엔드포인트 구현하거나,
> Flutter에서 카카오 전용 경로로 수정 필요.

---

### 1.3 로그아웃 — `POST /auth/logout`

```
Flutter:  POST /auth/logout
서버:     POST /api/auth/logout  ✅ 일치
```

**서버 Response:** `200 OK`
```json
{
  "success": true,
  "message": "로그아웃되었습니다.",
  "logout_required": true
}
```

> 서버에 전체 디바이스 로그아웃도 있음: `POST /api/auth/logout-all`

---

### 1.4 토큰 갱신 — `POST /auth/refresh`

```
Flutter:  POST /auth/refresh
서버:     ❌ 해당 엔드포인트 없음
```

**Flutter 호출 (기대):**
```dart
final response = await authService.refreshToken({
  'refresh_token': 'current_refresh_token',
});
```

> **TODO:** 서버에서 토큰 갱신 엔드포인트 구현 필요.
> 현재 서버는 Sanctum 토큰 방식이라 별도 refresh 없이 재로그인 필요.
> `AuthInterceptor`의 자동 갱신 로직 조정 필요할 수 있음.

---

### 1.5 현재 사용자 조회 (추가 활용 가능)

```
서버:  GET /api/auth/user  💡 Flutter에서 활용 가능
```

**서버 Response:** `200 OK`
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "홍길동",
    "email": "user@example.com"
  }
}
```

> `AuthController`에서 사용자 정보 갱신 시 활용 가능.

---

## 2. 코스 (CourseService)

> **파일:** `lib/infra/api/course_service.dart`
> **baseUrl:** `AppConfig.baseUrl` → 서버: `/api/parkgolf`

### 경로 매핑 정리

```
Flutter 경로                  서버 실제 경로                   상태
────────────────────────────────────────────────────────────────
GET /courses                → (없음, 검색으로 대체 가능)       ⚠️
GET /courses/{id}           → GET /api/parkgolf/{id}          ✅ 경로만 다름
GET /courses/search         → GET /api/parkgolf/search        ✅ 경로만 다름
GET /courses/nearby         → GET /api/parkgolf/nearby        ✅ 경로만 다름
GET /courses/popular        → (없음)                          ❌ 서버 구현 필요
GET /courses/{id}/holes     → (없음)                          ❌ 서버 구현 필요
```

### 2.1 코스 검색 — `GET /courses/search`

```
Flutter:  GET /courses/search?query=...
서버:     GET /api/parkgolf/search?name=...  ✅ 파라미터명 다름
```

**Flutter 호출:**
```dart
final response = await courseService.searchCourses(
  query: '서울',
  page: 1,
  limit: 20,
);
```

**서버 Query Parameters:**

| Flutter 파라미터 | 서버 파라미터 | 타입 | 설명 |
|----------------|------------|------|------|
| `query` | `name` | string | 코스명 검색 |
| `page` | (없음) | int | 서버는 `per_page`로 페이지 크기 제어 |
| `limit` | `per_page` | int | 기본 20, 최대 100 |
| (없음) | `region` | string | 지역 필터 (서버 추가 기능) |
| (없음) | `lat` | double | 위도 (반경 검색 시) |
| (없음) | `lon` | double | 경도 (반경 검색 시) |
| (없음) | `radius` | double | 반경 km (기본 10, 최대 100) |

**서버 Response:** `200 OK` — 페이지네이션된 코스 목록

**Flutter 모델 매핑 (`CourseSearchResultModel`):**
```
서버 필드           → Flutter 필드        비고
───────────────────────────────────────────────
id                → id                 타입 변환 필요 (int→String)
name              → name               ✅
address           → address            ✅ (서버 필드명 확인 필요)
hole_count        → holeCount          ✅ (snake→camel)
par               → par                ✅ (서버 필드 확인 필요)
rating            → rating             ❌ 서버에 없을 수 있음
review_count      → reviewCount        ❌ 서버에 없을 수 있음
distance          → distance           ✅ (반경 검색 시 포함)
image_url         → imageUrl           ❌ 서버에 없을 수 있음
```

---

### 2.2 주변 코스 조회 — `GET /courses/nearby`

```
Flutter:  GET /courses/nearby?latitude=...&longitude=...
서버:     GET /api/parkgolf/nearby?lat=...&lon=...  ⚠️ 파라미터명 다름
```

**파라미터 매핑:**

| Flutter | 서버 | 필수 | 설명 |
|---------|------|------|------|
| `latitude` | `lat` | O | 위도 (-90~90) |
| `longitude` | `lon` | O | 경도 (-180~180) |
| `radius` | `radius` | X | 반경 km (기본 10, 범위 0.1~100) |
| `limit` | `limit` | X | 최대 결과 수 (기본 20, 최대 100) |

**서버 Response:** `200 OK`
```json
{
  "center": { "lat": 37.5, "lon": 127.0 },
  "radius": 10,
  "count": 5,
  "courses": [
    {
      "id": 1,
      "name": "○○ 파크골프장",
      "region": "서울",
      "lat": 37.51,
      "lon": 127.01,
      "distance": 1.2
    }
  ]
}
```

**Flutter 모델 매핑 (`CourseMarkerModel`):**
```
서버 필드    → Flutter 필드     비고
──────────────────────────────────────
id         → id              int→String 변환
name       → name            ✅
address    → address         서버: region만 있음
lat        → latitude        ✅ 필드명 다름
lon        → longitude       ✅ 필드명 다름
hole_count → holeCount       서버에 없을 수 있음
par        → par             서버에 없을 수 있음
rating     → rating          서버에 없음
distance   → distance        ✅
image_url  → imageUrl        서버에 없음
```

---

### 2.3 코스 상세 — `GET /courses/{id}`

```
Flutter:  GET /courses/{id}
서버:     GET /api/parkgolf/{id}  ✅ 경로만 다름
```

**서버 Response:** `200 OK` — 코스 상세 정보

---

### 2.4 지역 목록 (서버 추가 기능)

```
서버:  GET /api/parkgolf/regions  💡 Flutter에서 활용 가능
```

**서버 Response:** `200 OK`
```json
["서울", "부산", "대구", "인천", ...]
```

> 코스 검색 필터 UI에서 활용 가능.

---

### 2.5 파크골프 통계 (서버 추가 기능)

```
서버:  GET /api/parkgolf/statistics  💡 Flutter에서 활용 가능
```

**서버 Response:** `200 OK`
```json
{
  "total": 500,
  "by_region": [{ "region": "서울", "count": 45 }],
  "with_coordinates": 480,
  "average_holes": 18.5
}
```

---

## 3. 라운드 (RoundService)

> **파일:** `lib/infra/api/round_service.dart`
> **서버 상태:** ❌ **전체 미구현** — 서버에서 구현 필요

### 서버에 구현 필요한 엔드포인트

| Method | 경로 | 설명 | Request 모델 | Response 모델 |
|--------|------|------|-------------|--------------|
| GET | `/rounds` | 라운드 목록 | `page`, `limit`, `status` | `List<RoundModel>` |
| GET | `/rounds/{id}` | 라운드 상세 | - | `RoundModel` |
| POST | `/rounds` | 라운드 생성 | `CreateRoundRequest` | `RoundModel` |
| POST | `/rounds/{id}/start` | 라운드 시작 | - | `RoundModel` |
| POST | `/rounds/{id}/complete` | 라운드 완료 | - | `RoundResultModel` |
| DELETE | `/rounds/{id}` | 라운드 취소 | - | `void` |
| POST | `/rounds/{roundId}/scores` | 스코어 입력 | `ScoreInputRequest` | `HoleScoreModel` |
| PUT | `/rounds/{roundId}/scores/{hole}` | 스코어 수정 | `ScoreInputRequest` | `HoleScoreModel` |
| GET | `/rounds/{id}/result` | 라운드 결과 | - | `RoundResultModel` |
| GET | `/rounds/{id}/scorecard` | 스코어카드 | - | `List<ScorecardModel>` |

### Request/Response JSON 스펙

**`CreateRoundRequest`:**
```json
{
  "courseId": "string (required)",
  "date": "2026-02-10T09:00:00Z (required, ISO 8601)",
  "playerIds": ["player1", "player2"],
  "memo": "string (optional)"
}
```

**`RoundModel` Response:**
```json
{
  "id": "string",
  "courseId": "string",
  "courseName": "string",
  "date": "2026-02-10T09:00:00Z",
  "holeCount": 18,
  "holePars": [3, 4, 3, 4, 3, 3, 4, 3, 4, 3, 4, 3, 3, 4, 3, 4, 3, 3],
  "players": [
    {
      "oderId": "string",
      "player": {
        "id": "string",
        "name": "홍길동",
        "nickname": "null|string",
        "profileImage": "null|string",
        "isMe": true
      },
      "scores": [
        { "holeNumber": 1, "par": 3, "score": 3, "memo": null }
      ],
      "rank": null,
      "isWinner": false
    }
  ],
  "status": "draft|in_progress|completed|cancelled",
  "memo": "null|string",
  "startedAt": "null|datetime",
  "completedAt": "null|datetime",
  "createdAt": "datetime",
  "updatedAt": "datetime"
}
```

**`ScoreInputRequest`:**
```json
{
  "roundId": "string (required)",
  "playerId": "string (required)",
  "holeNumber": 1,
  "score": 3,
  "memo": "null|string"
}
```

**`RoundResultModel` Response:**
```json
{
  "id": "string",
  "courseName": "string",
  "date": "datetime",
  "totalHoles": 18,
  "holePars": [3, 4, 3, ...],
  "players": [
    {
      "playerId": "string",
      "playerName": "홍길동",
      "profileImage": "null|string",
      "scores": [3, 4, 2, ...],
      "totalScore": 54,
      "scoreVsPar": -2,
      "isWinner": true,
      "rank": 1
    }
  ]
}
```

---

## 4. 기록 (RecordService)

> **파일:** `lib/infra/api/record_service.dart`
> **서버 상태:** ❌ **전체 미구현** — 서버에서 구현 필요

### 서버에 구현 필요한 엔드포인트

| Method | 경로 | 설명 | Response 모델 |
|--------|------|------|--------------|
| GET | `/records/statistics` | 내 기록 통계 | `RecordStatisticsModel` |
| GET | `/records/rounds` | 라운드 기록 목록 | `List<RoundRecordModel>` |
| GET | `/records/rounds/{id}` | 라운드 기록 상세 | `RoundRecordModel` |
| GET | `/records/monthly` | 월별 요약 | `List<MonthlyRecordSummaryModel>` |
| GET | `/records/courses/{courseId}` | 코스별 기록 | `CourseRecordModel` |
| GET | `/records/courses` | 코스별 기록 목록 | `List<CourseRecordModel>` |

### Response JSON 스펙

**`RecordStatisticsModel`:**
```json
{
  "totalRounds": 42,
  "averageScore": 68.5,
  "bestScore": 58,
  "worstScore": 82,
  "totalHolesPlayed": 756,
  "eagleOrBetterCount": 5,
  "birdieCount": 45,
  "parCount": 320,
  "bogeyCount": 180,
  "doubleOrWorseCount": 30,
  "winRate": 0.35,
  "winCount": 15,
  "lossCount": 27,
  "mostPlayedCourseId": "string",
  "mostPlayedCourseName": "○○ 파크골프장",
  "lastPlayedAt": "datetime"
}
```

**`RoundRecordModel`:**
```json
{
  "id": "string",
  "courseId": "string",
  "courseName": "○○ 파크골프장",
  "date": "datetime",
  "totalScore": 68,
  "scoreVsPar": -4,
  "rank": 1,
  "playerCount": 4,
  "courseThumbnail": "null|string",
  "playerNames": ["홍길동", "김철수"]
}
```

**`MonthlyRecordSummaryModel`:**
```json
{
  "year": 2026,
  "month": 2,
  "roundCount": 5,
  "averageScore": 70.2,
  "bestScore": 65,
  "winCount": 2
}
```

**`CourseRecordModel`:**
```json
{
  "courseId": "string",
  "courseName": "○○ 파크골프장",
  "playCount": 8,
  "averageScore": 69.3,
  "bestScore": 62,
  "lastPlayedAt": "datetime"
}
```

---

## 5. 클럽 (ClubService)

> **파일:** `lib/infra/api/club_service.dart`
> **서버 상태:** ❌ **전체 미구현** — 서버에서 구현 필요

### 서버에 구현 필요한 엔드포인트

| Method | 경로 | 설명 | Response 모델 |
|--------|------|------|--------------|
| GET | `/clubs/my` | 내 클럽 목록 | `List<ClubModel>` |
| GET | `/clubs/{id}` | 클럽 상세 | `ClubModel` |
| GET | `/clubs/search` | 클럽 검색 | `List<ClubSearchResultModel>` |
| GET | `/clubs/{id}/feed` | 클럽 피드 | `List<FeedItemModel>` |
| GET | `/clubs/{id}/feed/{fid}` | 피드 상세 | `FeedItemModel` |
| POST | `/clubs/{id}/feed` | 피드 작성 | `FeedItemModel` |
| POST | `/clubs/{id}/feed/{fid}/like` | 좋아요 | `void` |
| DELETE | `/clubs/{id}/feed/{fid}/like` | 좋아요 취소 | `void` |
| GET | `/clubs/{id}/feed/{fid}/comments` | 댓글 목록 | `List<FeedCommentModel>` |
| POST | `/clubs/{id}/feed/{fid}/comments` | 댓글 작성 | `FeedCommentModel` |
| GET | `/clubs/{id}/members` | 멤버 목록 | `List<ClubMemberModel>` |
| POST | `/clubs/{id}/join` | 클럽 가입 | `ClubMemberModel` |
| DELETE | `/clubs/{id}/leave` | 클럽 탈퇴 | `void` |

### Response JSON 스펙

**`ClubModel`:**
```json
{
  "id": "string",
  "name": "파크골프 동호회",
  "description": "null|string",
  "imageUrl": "null|string",
  "thumbnailUrl": "null|string",
  "memberCount": 25,
  "region": "null|string",
  "ownerId": "null|string",
  "ownerName": "null|string",
  "isPublic": true,
  "requiresApproval": false,
  "createdAt": "datetime",
  "updatedAt": "datetime"
}
```

**`FeedItemModel`:**
```json
{
  "id": "string",
  "clubId": "string",
  "authorId": "string",
  "authorName": "홍길동",
  "authorImage": "null|string",
  "content": "오늘 라운드 후기입니다!",
  "imageUrls": ["https://..."],
  "likeCount": 5,
  "commentCount": 3,
  "isLiked": false,
  "isBookmarked": false,
  "createdAt": "datetime",
  "updatedAt": "datetime"
}
```

**`CreateFeedRequest`:**
```json
{
  "clubId": "string (required)",
  "content": "string (required)",
  "imageUrls": ["string"]
}
```

**`CreateCommentRequest`:**
```json
{
  "content": "string (required)",
  "parentId": "null|string (대댓글 시)"
}
```

---

## 6. 서버 전용 API (앱 미사용)

현재 서버에 존재하지만 Flutter 앱에서 아직 연동하지 않는 API들.
필요 시 새로운 Service 파일 생성으로 활용 가능.

### 6.1 프로필 관리 — `/api/profile/*`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/profile` | Sanctum | 프로필 + 구독 정보 조회 |
| POST | `/api/profile/subscribe` | Sanctum | 부서 구독 토글 |
| POST | `/api/profile/photo` | Sanctum | 프로필 사진 변경 (multipart) |
| POST | `/api/profile/delete` | Sanctum | 계정 삭제 |

### 6.2 금시세 — `/api/gold/*`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/gold/latest` | Public | 최신 금시세 |
| GET | `/api/gold/history` | Public | 시세 히스토리 (`period`, `type`, `market`) |
| GET | `/api/gold/statistics` | Public | 시세 통계 |

### 6.3 교회 콘텐츠 — `/api/church/*`, `/api/c/*`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/c/{slug}` | Public | 교회별 콘텐츠 조회 |
| GET | `/api/c/{slug}/departments` | Public | 교회별 부서 목록 |
| GET | `/api/contents/{id}` | Public | 콘텐츠 상세 |
| GET | `/api/church/{slug}/contents` | Public | 교회 콘텐츠 (정렬, 필터 지원) |
| GET | `/api/church/{slug}/videos` | Public | 교회 비디오 |

### 6.4 피드 — `/api/feed`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/feed` | Public | 전체 피드 조회 (15개/페이지) |
| POST | `/api/feed` | Sanctum | 피드 작성 (이미지/비디오 지원) |

### 6.5 댓글 — `/api/contents/{id}/comments`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/contents/{id}/comments` | Public | 댓글 목록 (20개/페이지) |
| POST | `/api/contents/{id}/comments` | Optional | 댓글 작성 (게스트 가능) |
| DELETE | `/api/contents/{cId}/comments/{cmId}` | Optional | 댓글 삭제 |

### 6.6 심링크 방문 추적 — `/api/symlink-visits/*`

| Method | 경로 | 인증 | 설명 |
|--------|------|------|------|
| GET | `/api/symlink-visits` | API Token | 방문 목록 |
| GET | `/api/symlink-visits/statistics` | API Token | 방문 통계 |
| POST | `/api/symlink-visits` | API Token | 방문 기록 생성 |

> 인증 방식: `Authorization: Bearer {token}` 또는 `X-API-Token: {token}`
> 환경변수 `SYMLINK_API_TOKENS`에 설정된 토큰

---

## 7. 공통 사항

### 7.1 인증 헤더

```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
Accept: application/json
```

> `AuthInterceptor`에서 자동 주입됨.

### 7.2 서버 응답 구조 vs Flutter ApiResponse

**서버 일반 응답:**
```json
{
  "success": true,
  "message": "처리 완료",
  "data": { ... }
}
```

**Flutter ApiResponse (Freezed):**
```dart
ApiResponse.success(data, message: '...', statusCode: 200, pagination: ...)
ApiResponse.error(message, statusCode: 500, errors: {...})
```

> **주의:** 서버 응답을 `ApiResponse`로 변환하는 로직이 각 RepositoryImpl에 필요.
> `BaseRepositoryMixin`에서 처리하되, 서버의 `success` 필드 기반 분기 추가 권장.

### 7.3 페이지네이션

**서버 (Laravel 기본):**
```json
{
  "current_page": 1,
  "data": [...],
  "last_page": 5,
  "per_page": 20,
  "total": 100,
  "next_page_url": "...",
  "prev_page_url": "..."
}
```

**Flutter PaginationModel:**
```dart
PaginationModel(
  currentPage: json['current_page'],
  totalPages: json['last_page'],
  totalItems: json['total'],
  itemsPerPage: json['per_page'],
  hasNext: json['next_page_url'] != null,
  hasPrevious: json['prev_page_url'] != null,
)
```

### 7.4 에러 응답

**서버 422 (유효성 검사):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["이메일 형식이 올바르지 않습니다."],
    "password": ["비밀번호는 필수입니다."]
  }
}
```

**Flutter ErrorInterceptor 매핑:**
```
422 → ValidationError (field errors 포함)
401 → AuthError
403 → AuthError (권한 없음)
404 → BusinessError (not found)
429 → NetworkError (rate limit)
5xx → SystemError
```

### 7.5 ID 타입 주의

```
서버: id는 int 타입 (Laravel auto-increment)
Flutter: id는 String 타입 (모델 정의)
→ JSON 역직렬화 시 int→String 변환 필요
→ fromJson에서 .toString() 처리 또는 JsonKey 커스텀 converter 사용
```

### 7.6 날짜 형식

```
서버:    "2026-02-10T09:00:00.000000Z" (ISO 8601, UTC)
Flutter: DateTime (Freezed가 자동 파싱)
```

### 7.7 파일 업로드 (multipart)

서버에서 파일 업로드가 필요한 경우 (프로필 사진, 피드 이미지 등):

```dart
// Dio FormData 사용
final formData = FormData.fromMap({
  'profile_photo': await MultipartFile.fromFile(filePath),
});
```

> Retrofit `@Part()` 데코레이터 또는 직접 Dio 호출 필요.

---

## 부록: 서버 API 구현 우선순위 제안

### P0 (필수 — 앱 핵심 기능)

| 우선순위 | 엔드포인트 그룹 | 이유 |
|---------|---------------|------|
| 1 | `POST /auth/social-login` | 카카오 로그인 연동 |
| 2 | `GET/POST /rounds/*` | 파크골프 라운드 핵심 기능 |
| 3 | `POST/PUT /rounds/{id}/scores` | 스코어 입력 핵심 기능 |
| 4 | `GET /records/statistics` | 내 기록 통계 |
| 5 | `GET /records/rounds` | 라운드 기록 목록 |

### P1 (중요 — 앱 주요 기능)

| 우선순위 | 엔드포인트 그룹 | 이유 |
|---------|---------------|------|
| 6 | `GET /courses/popular` | 인기 코스 추천 |
| 7 | `GET /courses/{id}/holes` | 코스 홀 정보 |
| 8 | `GET /records/monthly` | 월별 기록 |
| 9 | `GET /records/courses` | 코스별 기록 |

### P2 (추가 — 소셜 기능)

| 우선순위 | 엔드포인트 그룹 | 이유 |
|---------|---------------|------|
| 10 | `GET/POST /clubs/*` | 클럽 CRUD |
| 11 | `GET/POST /clubs/{id}/feed` | 클럽 피드 |
| 12 | `POST /clubs/{id}/join` | 클럽 가입/탈퇴 |
| 13 | 댓글/좋아요 관련 | 소셜 인터랙션 |

### P3 (개선)

| 우선순위 | 엔드포인트 그룹 | 이유 |
|---------|---------------|------|
| 14 | `POST /auth/refresh` | 토큰 갱신 (UX 개선) |
| 15 | 경로 통일 (`/courses` ↔ `/parkgolf`) | 클라이언트-서버 일관성 |
