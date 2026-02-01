# API HTTP Tests

이 디렉토리에는 REST API 테스트를 위한 `.http` 파일들이 포함되어 있습니다.

## 사용 방법

### 1. VS Code / IntelliJ / PHPStorm

#### VS Code
- **REST Client** 확장 설치: `humao.rest-client`
- `.http` 파일 열기
- 각 요청 위의 `Send Request` 링크 클릭

#### IntelliJ / PHPStorm
- 내장 HTTP Client 사용
- `.http` 파일 열기
- 각 요청 옆의 실행 버튼 클릭

### 2. 환경 설정

`http-client.env.json` 파일에서 환경별 설정:

```json
{
  "local": {
    "baseUrl": "http://pcaview.test/api",
    "token": "your-auth-token-here"
  }
}
```

### 3. 인증 토큰 얻기

```http
POST {{baseUrl}}/auth/login
Content-Type: application/json

{
  "email": "your@email.com",
  "password": "your-password",
  "device_name": "http-client"
}
```

응답에서 `token` 값을 복사하여 `http-client.env.json`의 `token` 필드에 붙여넣기.

## 테스트 파일

### contents.http
Church contents API 엔드포인트 테스트:
- ✅ GET /api/c/{church} - Church 콘텐츠 목록
- ✅ GET /api/c/{church}/departments - Church 부서 목록
- ✅ GET /api/contents/{id} - 단일 콘텐츠 조회
- ✅ DELETE /api/contents/{id} - 콘텐츠 삭제 (인증 필요)
- ✅ POST /api/contents/{id}/delete - 콘텐츠 삭제 (모바일 호환)

## News 필터링 테스트

News 타입 콘텐츠는 저작권 보호를 위해 본문의 1/3만 표시됩니다:

**예시:**
```
원본 body: "뉴스 본문 내용이 300자입니다..." (300자)
API 응답: "뉴스 본문 내용이 100자입니..." (100자 + "...")
```

**테스트 방법:**
1. DB에 news 타입 콘텐츠 생성 (긴 본문)
2. `GET /api/c/{church}` 호출
3. 응답의 news 타입 body가 1/3 길이 + "..." 인지 확인
4. 다른 타입(bulletin, video 등)은 전체 본문 확인

## 에러 응답 예시

### 404 Not Found
```json
{
  "success": false,
  "message": "Church not found"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "logout_required": true
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "삭제 권한이 없습니다."
}
```

## 성공 응답 예시

### GET /api/c/{church}
```json
{
  "success": true,
  "message": "Contents retrieved successfully",
  "data": {
    "church": {
      "id": 1,
      "name": "Test Church",
      "slug": "test-church"
    },
    "contents": [
      {
        "id": 1,
        "type": "news",
        "title": "뉴스 제목",
        "body": "뉴스 본문 일부...",
        "published_at": "2026-01-15T10:00:00.000000Z",
        "user": { ... },
        "images": [ ... ],
        "departments": [ ... ],
        "tags": [ ... ],
        "comments_count": 5
      }
    ],
    "total": 10
  }
}
```

### DELETE /api/contents/{id}
```json
{
  "success": true,
  "message": "콘텐츠가 삭제되었습니다."
}
```

## 팁

1. **빠른 테스트**: 각 요청 위의 `###` 주석은 구분자로 사용됩니다.
2. **변수 사용**: `{{baseUrl}}`, `{{token}}` 같은 변수로 환경별 설정 가능
3. **순차 실행**: 여러 요청을 순서대로 테스트할 때 유용
4. **응답 저장**: 응답을 파일로 저장하여 비교 가능

## 실제 사용 예시

```http
### 1. 로그인하여 토큰 얻기
POST {{baseUrl}}/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password"
}

### 2. 얻은 토큰으로 콘텐츠 조회
GET {{baseUrl}}/c/msch
Authorization: Bearer your-token-from-step-1

### 3. 콘텐츠 삭제
DELETE {{baseUrl}}/contents/123
Authorization: Bearer your-token-from-step-1
```
