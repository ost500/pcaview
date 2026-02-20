# YTPlayer API 보안 검증 가이드

## 개요

YTPlayer API는 HMAC-SHA256 서명 검증을 통해 요청의 무결성과 인증을 보장합니다.

## 보안 메커니즘

### 1. HMAC 서명 검증
- **알고리즘**: HMAC-SHA256
- **서명 대상**: timestamp + nonce + request body
- **비밀키**: 서버와 앱이 공유하는 시크릿

### 2. 타임스탬프 검증
- 요청 타임스탬프가 현재 시간과 5분 이상 차이나면 거부
- 재생 공격(Replay Attack) 방지

### 3. Nonce (일회용 토큰)
- 각 요청마다 고유한 nonce 생성
- 5분 동안 동일한 nonce 재사용 불가
- 중복 요청 방지

### 4. 중복 적립 방지
- 동일 사용자의 동일 리워드 타입에 대해 60초 이내 중복 적립 차단
- 비디오 URL이 있는 경우 URL 기반 중복 체크

---

## 클라이언트 구현 (Kotlin/Android)

### 1. 의존성 추가

```gradle
dependencies {
    implementation 'com.squareup.okhttp3:okhttp:4.11.0'
    implementation 'com.google.code.gson:gson:2.10.1'
}
```

### 2. HMAC 서명 생성 함수

```kotlin
import java.security.MessageDigest
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec
import java.util.UUID

object YTPlayerSecurity {
    private const val SECRET_KEY = "your-secret-key-change-this-in-production" // 서버와 동일한 키

    /**
     * HMAC-SHA256 서명 생성
     */
    fun generateSignature(body: String, timestamp: Long, nonce: String): String {
        val payload = "$timestamp$nonce$body"
        val secretKeySpec = SecretKeySpec(SECRET_KEY.toByteArray(), "HmacSHA256")
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(secretKeySpec)
        val hmacBytes = mac.doFinal(payload.toByteArray())
        return hmacBytes.joinToString("") { "%02x".format(it) }
    }

    /**
     * Nonce 생성 (UUID 사용)
     */
    fun generateNonce(): String {
        return UUID.randomUUID().toString()
    }

    /**
     * 현재 Unix 타임스탬프 (초 단위)
     */
    fun getCurrentTimestamp(): Long {
        return System.currentTimeMillis() / 1000
    }
}
```

### 3. API 요청 예제

```kotlin
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import com.google.gson.Gson
import java.io.IOException

data class RewardRequest(
    val encrypted: String,
    val reward_type: String,
    val where: String? = null,
    val video_url: String? = null,
    val video_time: Int? = null,
    val video_stringtime: String? = null
)

object YTPlayerApi {
    private val client = OkHttpClient()
    private val gson = Gson()
    private const val BASE_URL = "https://pcaview.abc/api/ytplayer"

    /**
     * 리워드 적립 요청
     */
    fun earnReward(
        encrypted: String,
        rewardType: String,
        where: String? = null,
        videoUrl: String? = null,
        videoTime: Int? = null,
        callback: (success: Boolean, message: String) -> Unit
    ) {
        val rewardRequest = RewardRequest(
            encrypted = encrypted,
            reward_type = rewardType,
            where = where,
            video_url = videoUrl,
            video_time = videoTime,
            video_stringtime = videoTime?.let { formatTime(it) }
        )

        val body = gson.toJson(rewardRequest)
        val timestamp = YTPlayerSecurity.getCurrentTimestamp()
        val nonce = YTPlayerSecurity.generateNonce()
        val signature = YTPlayerSecurity.generateSignature(body, timestamp, nonce)

        val requestBody = body.toRequestBody("application/json".toMediaType())

        val request = Request.Builder()
            .url("$BASE_URL/reward")
            .post(requestBody)
            .addHeader("Content-Type", "application/json")
            .addHeader("X-YTPlayer-Signature", signature)
            .addHeader("X-YTPlayer-Timestamp", timestamp.toString())
            .addHeader("X-YTPlayer-Nonce", nonce)
            .build()

        client.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                callback(false, "Network error: ${e.message}")
            }

            override fun onResponse(call: Call, response: Response) {
                val responseBody = response.body?.string()
                if (response.isSuccessful) {
                    callback(true, "Reward earned successfully: $responseBody")
                } else {
                    callback(false, "Error: ${response.code} - $responseBody")
                }
            }
        })
    }

    private fun formatTime(seconds: Int): String {
        val hours = seconds / 3600
        val minutes = (seconds % 3600) / 60
        val secs = seconds % 60
        return String.format("%02d:%02d:%02d", hours, minutes, secs)
    }
}
```

### 4. 사용 예제

```kotlin
// 비디오 시청 리워드 적립
val userHash = "user_hash_12345" // 사용자 식별자 (해시 처리된 값)

YTPlayerApi.earnReward(
    encrypted = userHash,
    rewardType = "watch",
    where = "home_feed",
    videoUrl = "https://youtube.com/watch?v=example",
    videoTime = 300 // 5분
) { success, message ->
    if (success) {
        println("✅ $message")
    } else {
        println("❌ $message")
    }
}

// 광고 시청 리워드
YTPlayerApi.earnReward(
    encrypted = userHash,
    rewardType = "ad",
    where = "video_player"
) { success, message ->
    println(if (success) "✅ Ad reward: $message" else "❌ Error: $message")
}
```

---

## 클라이언트 구현 (Swift/iOS)

### HMAC 서명 생성

```swift
import Foundation
import CommonCrypto

struct YTPlayerSecurity {
    static let secretKey = "your-secret-key-change-this-in-production"

    static func generateSignature(body: String, timestamp: Int64, nonce: String) -> String {
        let payload = "\(timestamp)\(nonce)\(body)"
        return hmacSHA256(message: payload, key: secretKey)
    }

    static func generateNonce() -> String {
        return UUID().uuidString
    }

    static func getCurrentTimestamp() -> Int64 {
        return Int64(Date().timeIntervalSince1970)
    }

    private static func hmacSHA256(message: String, key: String) -> String {
        let cKey = key.cString(using: .utf8)!
        let cData = message.cString(using: .utf8)!
        var result = [CUnsignedChar](repeating: 0, count: Int(CC_SHA256_DIGEST_LENGTH))

        CCHmac(CCHmacAlgorithm(kCCHmacAlgSHA256), cKey, key.count, cData, message.count, &result)

        return result.map { String(format: "%02x", $0) }.joined()
    }
}
```

---

## 에러 응답

### 1. 서명 검증 실패
```json
{
  "success": false,
  "error": "Invalid signature"
}
```
**HTTP Status**: 401 Unauthorized

### 2. 타임스탬프 만료
```json
{
  "success": false,
  "error": "Request timestamp expired"
}
```
**HTTP Status**: 401 Unauthorized

### 3. Nonce 중복
```json
{
  "success": false,
  "error": "Duplicate request (nonce already used)"
}
```
**HTTP Status**: 409 Conflict

### 4. 중복 적립
```json
{
  "success": false,
  "error": "Duplicate reward request detected"
}
```
**HTTP Status**: 400 Bad Request

---

## 환경 변수 설정

### .env 파일

```env
# YTPlayer API Settings
YTPLAYER_SIGNATURE_SECRET=your-secret-key-change-this-in-production
YTPLAYER_REQUEST_TIMEOUT=300
YTPLAYER_DUPLICATE_PREVENTION=true
YTPLAYER_DUPLICATE_WINDOW=60
```

### 프로덕션 환경 주의사항

1. **비밀키 관리**
   - `YTPLAYER_SIGNATURE_SECRET`은 절대 공개하지 마세요
   - 앱 배포 시 난독화 처리
   - 주기적으로 키 로테이션

2. **HTTPS 필수**
   - 프로덕션 환경에서는 반드시 HTTPS 사용
   - 중간자 공격(MITM) 방지

3. **레이트 리미팅**
   - 과도한 요청 방지를 위한 rate limiting 구현 권장

---

## 테스트

### cURL 예제

```bash
#!/bin/bash

SECRET="your-secret-key-change-this-in-production"
TIMESTAMP=$(date +%s)
NONCE=$(uuidgen)
BODY='{"encrypted":"user_hash_12345","reward_type":"watch","video_time":300}'

# HMAC-SHA256 서명 생성
PAYLOAD="${TIMESTAMP}${NONCE}${BODY}"
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

# API 요청
curl -X POST https://pcaview.abc/api/ytplayer/reward \
  -H "Content-Type: application/json" \
  -H "X-YTPlayer-Signature: $SIGNATURE" \
  -H "X-YTPlayer-Timestamp: $TIMESTAMP" \
  -H "X-YTPlayer-Nonce: $NONCE" \
  -d "$BODY"
```

---

## 보안 체크리스트

- [ ] 비밀키가 코드에 하드코딩되지 않았는가?
- [ ] HTTPS를 사용하고 있는가?
- [ ] 타임스탬프 검증이 활성화되어 있는가?
- [ ] Nonce가 매 요청마다 고유한 값으로 생성되는가?
- [ ] 중복 적립 방지가 활성화되어 있는가?
- [ ] 프로덕션 환경에서 적절한 타임아웃 설정이 되어 있는가?
