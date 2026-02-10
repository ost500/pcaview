# PCAview 서버 API 명세서

> **Base URL**: `https://{domain}/api`
> **Framework**: Laravel 12
> **인증**: Laravel Sanctum (Bearer Token)
> **최종 업데이트**: 2026-02-10

---

## 목차

1. [인증 (Auth)](#1-인증-auth)
2. [카카오 소셜 로그인](#2-카카오-소셜-로그인)
3. [파크골프 코스](#3-파크골프-코스)
4. [피드 (Feed)](#4-피드-feed)
5. [콘텐츠 (Contents)](#5-콘텐츠-contents)
6. [댓글 (Comments)](#6-댓글-comments)
7. [프로필 (Profile)](#7-프로필-profile)
8. [금시세 (Gold Price)](#8-금시세-gold-price)
9. [교회 콘텐츠 (Church Contents)](#9-교회-콘텐츠-church-contents)
10. [심링크 방문 (Symlink Visits)](#10-심링크-방문-symlink-visits)

---

## 공통 사항

### 인증 방식

Sanctum Bearer Token을 사용합니다. 인증이 필요한 엔드포인트에는 다음 헤더를 포함해야 합니다:

```
Authorization: Bearer {token}
```

토큰은 로그인 API 응답에서 발급됩니다. **토큰 갱신(refresh) 기능은 없습니다.** 토큰이 만료되면 재로그인이 필요합니다.

### 페이지네이션

Laravel 기본 페이지네이션 형식을 사용합니다:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "last_page_url": "...",
  "links": [],
  "next_page_url": "...",
  "path": "...",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 100
}
```

### 공통 에러 응답

```json
{
  "success": false,
  "message": "에러 메시지"
}
```

| HTTP 상태 코드 | 설명 |
|---|---|
| 401 | 인증 실패 (토큰 없음/만료) |
| 403 | 권한 없음 |
| 404 | 리소스를 찾을 수 없음 |
| 422 | 유효성 검증 실패 |

---

## 1. 인증 (Auth)

### POST `/api/auth/login`

이메일/비밀번호 로그인. Sanctum 토큰을 발급합니다.

**인증**: 불필요

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `email` | string | O | 이메일 주소 |
| `password` | string | O | 비밀번호 |
| `device_name` | string | X | 디바이스명 (기본: User-Agent) |

**Response** `200 OK`:

```json
{
  "success": true,
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "홍길동",
    "email": "user@example.com",
    "profile_photo_url": "https://...",
    "profile_photo": "https://...",
    "email_verified_at": "2025-01-01T00:00:00.000000Z",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Error** `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

### POST `/api/auth/logout`

현재 토큰을 폐기합니다.

**인증**: 필수 (`auth:sanctum`)

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "로그아웃되었습니다.",
  "logout_required": true
}
```

---

### POST `/api/auth/logout-all`

사용자의 모든 디바이스 토큰을 폐기합니다.

**인증**: 필수 (`auth:sanctum`)

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "모든 기기에서 로그아웃되었습니다.",
  "logout_required": true
}
```

---

### GET `/api/auth/user`

현재 인증된 사용자 정보를 조회합니다.

**인증**: 필수 (`auth:sanctum`)

**Response** `200 OK`:

```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "홍길동",
    "email": "user@example.com",
    "kakao_id": "123456789",
    "profile_photo_url": "https://...",
    "profile_photo": "https://...",
    "email_verified_at": "2025-01-01T00:00:00.000000Z",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

---

## 2. 카카오 소셜 로그인

### POST `/api/auth/kakao/callback`

모바일 앱용 카카오 로그인. 카카오 액세스 토큰을 검증하고 Sanctum 토큰을 발급합니다.

**인증**: 불필요

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `access_token` | string | O | 카카오 SDK에서 받은 액세스 토큰 |
| `user_id` | string | O | 카카오 사용자 ID |
| `nickname` | string | X | 카카오 닉네임 |

**처리 흐름**:
1. 카카오 API (`https://kapi.kakao.com/v2/user/me`)로 토큰 검증
2. `user_id` 일치 여부 확인
3. 기존 사용자 조회 (kakao_id → email 순서) 또는 신규 생성
4. 카카오 프로필 이미지를 S3에 저장
5. Sanctum 토큰 발급

**Response** `200 OK`:

```json
{
  "success": true,
  "token": "2|xyz789...",
  "user": {
    "id": 1,
    "name": "카카오유저",
    "email": "123456789@kakao.pcaview.com",
    "profile_photo_url": "https://s3.../profile-images/kakao/..."
  }
}
```

**Error** `401`:

```json
{
  "success": false,
  "message": "Invalid Kakao token"
}
```

---

## 3. 파크골프 코스

### GET `/api/parkgolf/search`

파크골프장을 검색합니다. 이름, 지역, 좌표 기반 검색을 지원합니다.

**인증**: 불필요

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `name` | string | X | 코스명 검색 (LIKE 검색) |
| `region` | string | X | 지역 필터 (정확히 일치) |
| `lat` | float | X | 위도 (좌표 검색 시 `lon`과 함께 필수) |
| `lon` | float | X | 경도 (좌표 검색 시 `lat`과 함께 필수) |
| `radius` | float | X | 검색 반경 km (기본: 10) |
| `per_page` | int | X | 페이지당 항목 수 (기본: 20, 최대: 100) |

**좌표 검색**: `lat`과 `lon`이 모두 제공되면 Haversine 공식으로 거리를 계산하고, `distance` 필드가 결과에 포함됩니다.

**Response** `200 OK` (Laravel 페이지네이션):

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "서울 파크골프장",
      "region": "서울",
      "address": "서울특별시 강남구 ...",
      "area": "12000",
      "holes": 36,
      "longitude": 127.0276,
      "latitude": 37.4979,
      "phone": "02-1234-5678",
      "description": "설명 텍스트",
      "detail_url": "https://...",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z",
      "distance": 2.35
    }
  ],
  "per_page": 20,
  "total": 150,
  "last_page": 8
}
```

> `distance` 필드는 좌표 검색 시에만 포함됩니다 (단위: km).

---

### GET `/api/parkgolf/nearby`

현재 위치 기준 주변 파크골프장을 조회합니다.

**인증**: 불필요

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `lat` | float | O | 위도 (-90 ~ 90) |
| `lon` | float | O | 경도 (-180 ~ 180) |
| `radius` | float | X | 검색 반경 km (기본: 10, 최소: 0.1, 최대: 100) |
| `limit` | int | X | 최대 결과 수 (기본: 20, 최소: 1, 최대: 100) |

**Response** `200 OK`:

```json
{
  "center": {
    "lat": 37.4979,
    "lon": 127.0276
  },
  "radius": 10,
  "count": 5,
  "courses": [
    {
      "id": 1,
      "name": "서울 파크골프장",
      "region": "서울",
      "address": "서울특별시 ...",
      "area": "12000",
      "holes": 36,
      "longitude": 127.0276,
      "latitude": 37.4979,
      "phone": "02-1234-5678",
      "description": "...",
      "detail_url": "https://...",
      "distance": 1.23
    }
  ]
}
```

---

### GET `/api/parkgolf/{id}`

특정 파크골프장의 상세 정보를 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `id` | int | 코스 ID |

**Response** `200 OK` (코스 객체 직접 반환):

```json
{
  "id": 1,
  "name": "서울 파크골프장",
  "region": "서울",
  "address": "서울특별시 강남구 ...",
  "area": "12000",
  "holes": 36,
  "longitude": 127.0276,
  "latitude": 37.4979,
  "phone": "02-1234-5678",
  "description": "설명 텍스트",
  "detail_url": "https://...",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z"
}
```

**Error** `404`: 코스를 찾을 수 없음

---

### GET `/api/parkgolf/regions`

전체 지역 목록을 조회합니다.

**인증**: 불필요

**Response** `200 OK` (문자열 배열):

```json
["강원", "경기", "경남", "경북", "광주", "대구", "대전", "부산", "서울", "울산", "인천", "전남", "전북", "제주", "충남", "충북"]
```

---

### GET `/api/parkgolf/statistics`

파크골프 코스 전체 통계를 조회합니다.

**인증**: 불필요

**Response** `200 OK`:

```json
{
  "total": 450,
  "by_region": [
    { "region": "경기", "count": 85 },
    { "region": "강원", "count": 62 },
    { "region": "경남", "count": 55 }
  ],
  "with_coordinates": 420,
  "average_holes": 18.5
}
```

---

### ParkGolfCourse 모델 필드

| 필드 | 타입 | 설명 |
|---|---|---|
| `id` | int | PK |
| `name` | string | 코스명 |
| `region` | string | 지역 |
| `address` | string | 주소 |
| `area` | string | 면적 |
| `holes` | int | 홀 수 |
| `longitude` | float (nullable) | 경도 |
| `latitude` | float (nullable) | 위도 |
| `phone` | string (nullable) | 전화번호 |
| `description` | string (nullable) | 설명 |
| `detail_url` | string (nullable) | 상세 페이지 URL |
| `created_at` | datetime | 생성일 |
| `updated_at` | datetime | 수정일 |

---

## 4. 피드 (Feed)

### GET `/api/feed`

전체 피드 목록을 조회합니다 (최신순, 페이지네이션).

**인증**: 불필요

**Response** `200 OK` (Laravel 페이지네이션):

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "church_id": 1,
      "department_id": null,
      "type": "html",
      "title": null,
      "body": "게시물 내용",
      "file_url": null,
      "thumbnail_url": "https://s3.../...",
      "video_url": null,
      "published_at": "2025-01-01T00:00:00.000000Z",
      "user": { "id": 1, "name": "..." },
      "church": { "id": 1, "name": "..." },
      "departments": [],
      "images": []
    }
  ],
  "per_page": 15,
  "total": 100
}
```

---

### POST `/api/feed`

피드 게시물을 작성합니다. 이미지/동영상 업로드를 지원합니다 (S3 저장).

**인증**: 필수 (`auth:sanctum`)

**Content-Type**: `multipart/form-data`

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `content` | string | 조건부 | 게시물 내용 (최대 5000자). content, images, video 중 하나 필수 |
| `church_id` | int | 조건부 | 교회 ID. `church_id` 또는 `department_id` 중 하나 필수 |
| `department_id` | int | 조건부 | 부서 ID. `church_id` 또는 `department_id` 중 하나 필수 |
| `images.*` | file | X | 이미지 파일 (각 최대 10MB) |
| `video` | file | X | 동영상 파일 (최대 500MB, mp4/mpeg/quicktime/avi/mkv) |

**동작 방식**:
- `church_id` 지정: 교회의 모든 부서에 게시물이 연결됩니다.
- `department_id` 지정: 해당 부서에만 게시물이 연결됩니다.

**Response** `201 Created`:

```json
{
  "success": true,
  "message": "모든 부서에 게시물이 작성되었습니다.",
  "content": { "id": 1, "..." : "..." }
}
```

**Error** `400`:

```json
{
  "success": false,
  "message": "교회 또는 부서를 선택해주세요."
}
```

---

## 5. 콘텐츠 (Contents)

### GET `/api/c/{church}`

특정 교회의 콘텐츠 목록을 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `church` | string | 교회 slug (예: `maple`, `goldang`) |

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `department_id` | int | X | 부서 ID로 필터링 |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "Contents retrieved successfully",
  "data": {
    "church": { "id": 1, "name": "...", "slug": "maple" },
    "contents": [
      {
        "id": 1,
        "type": "html",
        "title": "...",
        "body": "...",
        "published_at": "...",
        "user": { "id": 1, "name": "..." },
        "images": [],
        "departments": [],
        "tags": [],
        "comments_count": 5
      }
    ],
    "total": 25
  }
}
```

> `news` 타입 콘텐츠는 저작권 보호를 위해 본문의 1/3만 반환됩니다.

---

### GET `/api/c/{church}/departments`

특정 교회의 부서 목록을 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `church` | string | 교회 slug |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "Departments retrieved successfully",
  "data": {
    "church": { "id": 1, "name": "...", "slug": "maple" },
    "departments": [
      { "id": 1, "name": "청년부", "church_id": 1, "created_at": "..." }
    ],
    "total": 5
  }
}
```

---

### GET `/api/contents/{id}`

특정 콘텐츠 상세 정보를 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `id` | int | 콘텐츠 ID |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "Content retrieved successfully",
  "data": {
    "id": 1,
    "type": "html",
    "title": "...",
    "body": "...",
    "user": { "id": 1, "name": "..." },
    "church": { "id": 1, "name": "..." },
    "departments": [],
    "images": [],
    "tags": [],
    "comments": []
  }
}
```

---

### DELETE `/api/contents/{id}`

콘텐츠를 삭제합니다. `POST /api/contents/{id}/delete`로도 호출 가능합니다.

**인증**: 필수 (`auth:sanctum`)

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `id` | int | 콘텐츠 ID |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "콘텐츠가 삭제되었습니다."
}
```

**Error** `403`:

```json
{
  "success": false,
  "message": "삭제 권한이 없습니다."
}
```

---

## 6. 댓글 (Comments)

### GET `/api/contents/{contentId}/comments`

특정 콘텐츠의 댓글 목록을 조회합니다 (최신순, 페이지네이션).

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `contentId` | int | 콘텐츠 ID |

**Response** `200 OK` (Laravel 페이지네이션):

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "content_id": 1,
      "user_id": 1,
      "guest_name": null,
      "body": "댓글 내용",
      "ip_address": "...",
      "created_at": "...",
      "user": { "id": 1, "name": "..." }
    }
  ],
  "per_page": 20,
  "total": 10
}
```

---

### POST `/api/contents/{contentId}/comments`

댓글을 작성합니다. 인증 사용자와 게스트 모두 작성 가능합니다.

**인증**: 선택적 (Sanctum guard 사용, 비인증 시 게스트로 처리)

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `contentId` | int | 콘텐츠 ID |

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `body` | string | O | 댓글 내용 (최대 1000자) |
| `guest_name` | string | X | 게스트 이름 (최대 50자). 미입력 시 IP 기반 자동 생성 |

**Response** `201 Created`:

```json
{
  "success": true,
  "message": "댓글이 작성되었습니다.",
  "comment": {
    "id": 1,
    "content_id": 1,
    "user_id": null,
    "guest_name": "게스트_192168",
    "body": "댓글 내용",
    "ip_address": "192.168.1.1",
    "created_at": "...",
    "user": null
  }
}
```

---

### DELETE `/api/contents/{contentId}/comments/{commentId}`

댓글을 삭제합니다.

**인증**: 선택적

**권한 규칙**:
- **인증 사용자**: 본인이 작성한 댓글만 삭제 가능
- **게스트**: 같은 IP + 같은 게스트 이름인 경우만 삭제 가능

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `contentId` | int | 콘텐츠 ID |
| `commentId` | int | 댓글 ID |

**Request Body** (게스트 삭제 시):

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `guest_name` | string | 조건부 | 게스트 댓글 삭제 시 필요 |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "댓글이 삭제되었습니다."
}
```

---

## 7. 프로필 (Profile)

> 모든 프로필 API는 인증이 필수입니다 (`auth:sanctum`).

### GET `/api/profile`

현재 사용자의 프로필 및 부서 구독 정보를 조회합니다.

**Response** `200 OK`:

```json
{
  "success": true,
  "user": { "id": 1, "name": "...", "email": "..." },
  "allDepartments": [
    { "id": 1, "name": "청년부", "church_id": 1 }
  ],
  "subscribedDepartmentIds": [1, 3, 5],
  "unsubscribedDepartmentIds": [2, 4]
}
```

> `subscribedDepartmentIds`: 사용자가 구독 중인 부서 ID 목록
> `unsubscribedDepartmentIds`: 사용자가 구독 해제한 부서 ID 목록

---

### POST `/api/profile/subscribe`

부서 구독/구독 취소를 토글합니다.

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `department_id` | int | O | 부서 ID |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "구독되었습니다.",
  "isSubscribed": true
}
```

---

### POST `/api/profile/photo`

프로필 사진을 업데이트합니다 (S3 저장).

**Content-Type**: `multipart/form-data`

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `profile_photo` | file | O | 이미지 파일 (jpeg/png/jpg/gif/webp, 최대 5MB) |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "프로필 사진이 변경되었습니다.",
  "profile_photo_url": "https://s3.../...",
  "user": { "id": 1, "name": "..." }
}
```

---

### POST `/api/profile/delete`

계정을 삭제합니다. 프로필 사진, 모든 토큰, 사용자 데이터가 삭제됩니다.

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "계정이 삭제되었습니다.",
  "logout_required": true
}
```

---

## 8. 금시세 (Gold Price)

> **참고**: 이 API는 `web.php`에 `api/gold` prefix로 정의되어 있습니다.
> 실제 경로는 `/api/gold/*`이지만 API 미들웨어가 아닌 웹 미들웨어를 사용합니다.

### GET `/api/gold/latest`

최신 국내 금시세를 조회합니다.

**인증**: 불필요

**Response** `200 OK`:

```json
{
  "data": {
    "price_date": "2025-06-15T00:00:00.000000Z",
    "pure_gold": { "buy": 95000, "sell": 92000 },
    "18k": { "buy": 71000, "sell": 68000 },
    "14k": { "buy": 55000, "sell": 52000 },
    "white_gold": { "buy": 48000, "sell": 45000 },
    "silver": { "buy": 1200, "sell": 1100 }
  }
}
```

> 가격 단위: 원/g

---

### GET `/api/gold/history`

금속 가격 차트 데이터를 조회합니다.

**인증**: 불필요

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `period` | string | X | 기간 (기본: `1m`). `7d`, `1m`, `3m`, `6m`, `1y`, `all` |
| `type` | string | X | 금속 종류 (기본: `pure`). 국내: `pure`, `18k`, `14k`, `white`, `silver` / 국제: `gold`, `platinum`, `palladium`, `silver` |
| `market` | string | X | 시장 (기본: `domestic`). `domestic`, `international` |

**Response** `200 OK` (국내):

```json
{
  "market": "domestic",
  "period": "1m",
  "type": "pure",
  "total_points": 30,
  "data": [
    {
      "date": "2025-05-15",
      "timestamp": 1747267200000,
      "price": 95000,
      "buy": 95000,
      "sell": 92000
    }
  ]
}
```

**Response** `200 OK` (국제):

```json
{
  "market": "international",
  "period": "1m",
  "type": "gold",
  "total_points": 30,
  "data": [
    {
      "date": "2025-05-15",
      "timestamp": 1747267200000,
      "price": 2350.50
    }
  ]
}
```

> 차트 데이터는 최대 500 포인트로 샘플링됩니다.

---

### GET `/api/gold/statistics`

특정 기간의 금시세 통계를 조회합니다.

**인증**: 불필요

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `period` | string | X | 기간 (기본: `1m`). `7d`, `1m`, `3m`, `6m`, `1y`, `all` |
| `type` | string | X | 금속 종류 (기본: `pure`). `pure`, `18k`, `14k`, `white`, `silver` |

**Response** `200 OK`:

```json
{
  "data": {
    "period": "1m",
    "type": "pure",
    "current": 95000,
    "highest": 98000,
    "lowest": 90000,
    "average": 93500,
    "change": {
      "value": 3000,
      "percentage": 3.26
    },
    "date_range": {
      "start": "2025-05-15T00:00:00.000000Z",
      "end": "2025-06-15T00:00:00.000000Z"
    }
  }
}
```

---

## 9. 교회 콘텐츠 (Church Contents)

> **참고**: 이 API는 `web.php`에 `api/church` prefix로 정의되어 있습니다.
> 실제 경로는 `/api/church/*`이지만 API 미들웨어가 아닌 웹 미들웨어를 사용합니다.

### GET `/api/church/{churchSlug}/contents`

교회 slug 기준 콘텐츠 목록을 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `churchSlug` | string | 교회 slug (예: `maple`) |

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `department_id` | int | X | 부서 ID 필터 |
| `sort_by` | string | X | 정렬 기준 (기본: `published_at`) |
| `sort_order` | string | X | 정렬 방향 (기본: `desc`) |
| `per_page` | int | X | 페이지당 항목 수 (기본: 20) |

**Response** `200 OK`:

```json
{
  "church": {
    "id": 1,
    "name": "...",
    "display_name": "...",
    "slug": "maple"
  },
  "contents": {
    "current_page": 1,
    "data": [],
    "per_page": 20,
    "total": 50
  }
}
```

---

### GET `/api/church/{churchSlug}/videos`

교회 slug 기준 동영상 콘텐츠만 조회합니다.

**인증**: 불필요

**Path/Query Parameters**: `/api/church/{churchSlug}/contents`와 동일

---

### GET `/api/church/id/{churchId}/contents`

교회 ID 기준 콘텐츠 목록을 조회합니다.

**인증**: 불필요

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `churchId` | int | 교회 ID |

**Query Parameters**: `/api/church/{churchSlug}/contents`와 동일

---

### GET `/api/church/id/{churchId}/videos`

교회 ID 기준 동영상 콘텐츠만 조회합니다.

**인증**: 불필요

**Path/Query Parameters**: `/api/church/id/{churchId}/contents`와 동일

---

## 10. 심링크 방문 (Symlink Visits)

> 모든 엔드포인트에 `api.token` 미들웨어가 적용됩니다 (별도 토큰 인증).

### GET `/api/symlink-visits`

방문 기록 목록을 조회합니다.

**인증**: 필수 (`api.token`)

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `ad_id` | string | X | 광고 ID 필터 |
| `start_date` | datetime | X | 시작 날짜 필터 |
| `end_date` | datetime | X | 종료 날짜 필터 |
| `per_page` | int | X | 페이지당 항목 수 (기본: 50, 최대: 100) |

**Response** `200 OK` (Laravel 페이지네이션):

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "ad_id": "coupang_001",
      "ip": "192.168.1.1",
      "user_agent": "...",
      "referer": "https://...",
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "per_page": 50,
  "total": 200
}
```

---

### GET `/api/symlink-visits/statistics`

방문 통계를 조회합니다.

**인증**: 필수 (`api.token`)

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `start_date` | datetime | X | 시작 날짜 필터 |
| `end_date` | datetime | X | 종료 날짜 필터 |
| `include_hourly` | bool | X | 시간대별 통계 포함 (최근 24시간) |
| `include_daily` | bool | X | 일별 통계 포함 (최근 30일) |

**Response** `200 OK`:

```json
{
  "total_visits": 500,
  "unique_ads": 15,
  "recent_visits": [],
  "hourly_visits": [
    { "hour": "2025-06-15 14:00:00", "count": 12 }
  ],
  "daily_visits": [
    { "date": "2025-06-15", "count": 45 }
  ]
}
```

---

### GET `/api/symlink-visits/count-by-ad`

광고 ID별 방문 횟수를 집계합니다.

**인증**: 필수 (`api.token`)

**Query Parameters**:

| 파라미터 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `start_date` | datetime | X | 시작 날짜 필터 |
| `end_date` | datetime | X | 종료 날짜 필터 |

**Response** `200 OK`:

```json
{
  "total_ads": 15,
  "ads": [
    {
      "ad_id": "coupang_001",
      "visit_count": 120,
      "last_visit": "2025-06-15T14:30:00.000000Z"
    }
  ]
}
```

---

### POST `/api/symlink-visits`

방문 기록을 생성합니다 (`updateOrCreate` - 같은 `ad_id`면 업데이트).

**인증**: 필수 (`api.token`)

**Request Body**:

| 필드 | 타입 | 필수 | 설명 |
|---|---|---|---|
| `ad_id` | string | O | 광고 ID (최대 255자) |
| `ip` | string | X | IP 주소 (기본: 요청 IP) |
| `user_agent` | string | X | User-Agent (기본: 요청 UA) |
| `referer` | string | X | Referer (기본: 요청 Referer) |

**Response** `201 Created`:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "ad_id": "coupang_001",
    "ip": "192.168.1.1",
    "user_agent": "...",
    "referer": "..."
  }
}
```

---

### GET `/api/symlink-visits/{adId}`

특정 광고의 방문 기록을 조회합니다.

**인증**: 필수 (`api.token`)

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `adId` | string | 광고 ID |

**Response** `200 OK`:

```json
{
  "id": 1,
  "ad_id": "coupang_001",
  "ip": "192.168.1.1",
  "user_agent": "...",
  "referer": "...",
  "created_at": "...",
  "updated_at": "..."
}
```

---

### DELETE `/api/symlink-visits/{adId}`

특정 광고의 방문 기록을 삭제합니다.

**인증**: 필수 (`api.token`)

**Path Parameters**:

| 파라미터 | 타입 | 설명 |
|---|---|---|
| `adId` | string | 광고 ID |

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "Visit record deleted successfully"
}
```

---

## 기타

### GET `/api/symlink`

쿠팡 파트너스 링크로 리다이렉트합니다.

**인증**: 불필요

**Response**: `302 Redirect` → `https://link.coupang.com/a/dmWLqr`
