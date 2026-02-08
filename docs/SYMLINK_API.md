# Symlink Visit API 사용 가이드

## 개요

Symlink Visit API는 외부 서비스에서 방문 기록을 수집하고 통계를 조회할 수 있는 RESTful API입니다.

## 인증

모든 API 엔드포인트는 **API 토큰 인증**이 필요합니다.

### 토큰 설정

`.env` 파일에 허용된 토큰을 설정합니다:

```env
SYMLINK_API_TOKENS=token1,token2,token3
```

### 토큰 전달 방법

#### 방법 1: Authorization Header (Bearer)
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.example.com/api/symlink-visits
```

#### 방법 2: X-API-Token Header
```bash
curl -H "X-API-Token: YOUR_TOKEN" https://api.example.com/api/symlink-visits
```

## API 엔드포인트

### 1. 전체 방문 기록 조회

**GET** `/api/symlink-visits`

#### 쿼리 파라미터
- `ad_id` (optional): 특정 광고 ID로 필터링
- `start_date` (optional): 시작 날짜 (YYYY-MM-DD)
- `end_date` (optional): 종료 날짜 (YYYY-MM-DD)
- `per_page` (optional): 페이지당 개수 (기본: 50, 최대: 100)

#### 예제
```bash
curl -H "X-API-Token: YOUR_TOKEN" \
  "https://api.example.com/api/symlink-visits?per_page=20&start_date=2026-01-01"
```

#### 응답
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "ad_id": "abc-123",
      "ip": "1.2.3.4",
      "user_agent": "Mozilla/5.0...",
      "referer": "https://example.com",
      "created_at": "2026-02-08T10:00:00.000000Z",
      "updated_at": "2026-02-08T10:00:00.000000Z"
    }
  ],
  "total": 100,
  "per_page": 20
}
```

---

### 2. 방문 기록 생성/업데이트

**POST** `/api/symlink-visits`

#### 요청 본문
```json
{
  "ad_id": "abc-123",
  "ip": "1.2.3.4",
  "user_agent": "Mozilla/5.0...",
  "referer": "https://example.com"
}
```

#### 예제
```bash
curl -X POST \
  -H "X-API-Token: YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"ad_id":"abc-123","ip":"1.2.3.4"}' \
  https://api.example.com/api/symlink-visits
```

#### 응답
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ad_id": "abc-123",
    "ip": "1.2.3.4",
    "user_agent": "Mozilla/5.0...",
    "referer": "https://example.com",
    "created_at": "2026-02-08T10:00:00.000000Z",
    "updated_at": "2026-02-08T10:00:00.000000Z"
  }
}
```

**참고**: 동일한 `ad_id`가 이미 존재하면 업데이트됩니다.

---

### 3. 통계 정보 조회

**GET** `/api/symlink-visits/statistics`

#### 쿼리 파라미터
- `start_date` (optional): 시작 날짜
- `end_date` (optional): 종료 날짜
- `include_hourly` (optional): 시간대별 통계 포함 (true/false)
- `include_daily` (optional): 일별 통계 포함 (true/false)

#### 예제
```bash
curl -H "X-API-Token: YOUR_TOKEN" \
  "https://api.example.com/api/symlink-visits/statistics?include_daily=true"
```

#### 응답
```json
{
  "total_visits": 191,
  "unique_ads": 79,
  "recent_visits": [...],
  "daily_visits": [
    {
      "date": "2026-02-08",
      "count": 15
    }
  ]
}
```

---

### 4. ad_id별 방문 횟수 집계

**GET** `/api/symlink-visits/count-by-ad`

#### 쿼리 파라미터
- `start_date` (optional): 시작 날짜
- `end_date` (optional): 종료 날짜

#### 예제
```bash
curl -H "X-API-Token: YOUR_TOKEN" \
  https://api.example.com/api/symlink-visits/count-by-ad
```

#### 응답
```json
{
  "total_ads": 79,
  "ads": [
    {
      "ad_id": "abc-123",
      "visit_count": 5,
      "last_visit": "2026-02-08 10:00:00"
    }
  ]
}
```

---

### 5. 특정 ad_id 조회

**GET** `/api/symlink-visits/{adId}`

#### 예제
```bash
curl -H "X-API-Token: YOUR_TOKEN" \
  https://api.example.com/api/symlink-visits/abc-123
```

#### 응답
```json
{
  "id": 1,
  "ad_id": "abc-123",
  "ip": "1.2.3.4",
  "user_agent": "Mozilla/5.0...",
  "referer": "https://example.com",
  "created_at": "2026-02-08T10:00:00.000000Z",
  "updated_at": "2026-02-08T10:00:00.000000Z"
}
```

---

### 6. 방문 기록 삭제

**DELETE** `/api/symlink-visits/{adId}`

#### 예제
```bash
curl -X DELETE \
  -H "X-API-Token: YOUR_TOKEN" \
  https://api.example.com/api/symlink-visits/abc-123
```

#### 응답
```json
{
  "success": true,
  "message": "Visit record deleted successfully"
}
```

---

## 에러 응답

### 401 Unauthorized - 토큰 없음
```json
{
  "success": false,
  "message": "API token is required. Please provide token in Authorization header or X-API-Token header."
}
```

### 403 Forbidden - 유효하지 않은 토큰
```json
{
  "success": false,
  "message": "Invalid API token."
}
```

### 503 Service Unavailable - 설정 안됨
```json
{
  "success": false,
  "message": "API access is not configured. Please contact administrator."
}
```

### 404 Not Found - 리소스 없음
```json
{
  "success": false,
  "message": "No query results for model..."
}
```

---

## 사용 예시

### JavaScript/Node.js
```javascript
const axios = require('axios');

const API_URL = 'https://api.example.com/api/symlink-visits';
const API_TOKEN = 'your-api-token';

// 방문 기록 생성
async function trackVisit(adId) {
  try {
    const response = await axios.post(API_URL, {
      ad_id: adId,
      ip: '1.2.3.4',
      user_agent: navigator.userAgent
    }, {
      headers: {
        'X-API-Token': API_TOKEN,
        'Content-Type': 'application/json'
      }
    });

    console.log('Visit tracked:', response.data);
  } catch (error) {
    console.error('Error:', error.response.data);
  }
}

// 통계 조회
async function getStatistics() {
  try {
    const response = await axios.get(`${API_URL}/statistics`, {
      headers: {
        'X-API-Token': API_TOKEN
      }
    });

    console.log('Statistics:', response.data);
  } catch (error) {
    console.error('Error:', error.response.data);
  }
}
```

### PHP
```php
<?php

$apiUrl = 'https://api.example.com/api/symlink-visits';
$apiToken = 'your-api-token';

// 방문 기록 생성
function trackVisit($adId) {
    global $apiUrl, $apiToken;

    $data = [
        'ad_id' => $adId,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Token: ' . $apiToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// 통계 조회
function getStatistics() {
    global $apiUrl, $apiToken;

    $ch = curl_init($apiUrl . '/statistics');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Token: ' . $apiToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
```

### Python
```python
import requests

API_URL = 'https://api.example.com/api/symlink-visits'
API_TOKEN = 'your-api-token'

# 방문 기록 생성
def track_visit(ad_id):
    headers = {
        'X-API-Token': API_TOKEN,
        'Content-Type': 'application/json'
    }

    data = {
        'ad_id': ad_id,
        'ip': '1.2.3.4'
    }

    response = requests.post(API_URL, json=data, headers=headers)
    return response.json()

# 통계 조회
def get_statistics():
    headers = {
        'X-API-Token': API_TOKEN
    }

    response = requests.get(f'{API_URL}/statistics', headers=headers)
    return response.json()

# 사용 예시
if __name__ == '__main__':
    result = track_visit('abc-123')
    print('Visit tracked:', result)

    stats = get_statistics()
    print('Statistics:', stats)
```

---

## 보안 권장사항

1. **토큰 보안**
   - API 토큰을 환경 변수로 관리하세요
   - 토큰을 코드에 하드코딩하지 마세요
   - 정기적으로 토큰을 갱신하세요

2. **HTTPS 사용**
   - 프로덕션 환경에서는 반드시 HTTPS를 사용하세요
   - HTTP로 토큰을 전송하지 마세요

3. **IP 화이트리스트** (선택사항)
   - 필요시 특정 IP에서만 접근 가능하도록 설정 가능

4. **Rate Limiting** (선택사항)
   - API 남용 방지를 위한 요청 제한 설정 고려
