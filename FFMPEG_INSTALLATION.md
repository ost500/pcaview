# FFmpeg 설치 가이드 (Production 서버)

## 문제 상황
```
[ERROR] FFmpeg thumbnail generation failed
{"content_id":6472,"return_code":127,"output":["sh: 1: ffmpeg: not found"]}
```

video 타입 contents의 자동 썸네일 생성을 위해 FFmpeg가 필요하지만 서버에 설치되어 있지 않습니다.

## 해결 방법

### 1. 코드 수정 (이미 적용됨)
FFmpeg가 없어도 에러가 발생하지 않도록 graceful하게 처리:
- FFmpeg 설치 여부를 사전 체크
- 설치되지 않은 경우 경고 로그만 남기고 스킵
- 애플리케이션 정상 동작 유지

### 2. 서버에 FFmpeg 설치 (권장)

#### Ubuntu/Debian 서버
```bash
# 패키지 업데이트
sudo apt update

# FFmpeg 설치
sudo apt install ffmpeg -y

# 설치 확인
ffmpeg -version
which ffmpeg
```

#### CentOS/RHEL 서버
```bash
# EPEL 저장소 활성화
sudo yum install epel-release -y

# FFmpeg 설치
sudo yum install ffmpeg -y

# 설치 확인
ffmpeg -version
which ffmpeg
```

#### Alpine Linux (Docker)
```bash
# 컨테이너에 FFmpeg 추가
apk add --no-cache ffmpeg

# 또는 Dockerfile에 추가
RUN apk add --no-cache ffmpeg
```

#### Docker Compose 사용 시
```dockerfile
# Dockerfile에 추가
FROM php:8.2-fpm

# ... 기존 설정 ...

# FFmpeg 설치 (Debian 기반)
RUN apt-get update && apt-get install -y \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# 또는 Alpine 기반
# RUN apk add --no-cache ffmpeg
```

#### macOS (개발 환경)
```bash
# Homebrew 사용
brew install ffmpeg

# 설치 확인
ffmpeg -version
```

### 3. 설치 확인

```bash
# FFmpeg 설치 확인
which ffmpeg
# 출력: /usr/bin/ffmpeg (또는 다른 경로)

# 버전 확인
ffmpeg -version
# 출력: ffmpeg version 4.x.x ...

# 썸네일 생성 테스트
ffmpeg -i test.mp4 -ss 00:00:01.000 -vframes 1 -q:v 2 test.jpg
```

### 4. Laravel 애플리케이션 재시작

FFmpeg 설치 후 Laravel 큐 워커 재시작:

```bash
# 큐 워커 재시작
php artisan queue:restart

# 또는 supervisor 사용 시
sudo supervisorctl restart laravel-worker:*

# 또는 systemd 사용 시
sudo systemctl restart laravel-worker
```

## 동작 확인

### 수동 썸네일 생성 테스트
```bash
# Laravel 프로젝트 디렉토리에서
php artisan thumbnails:generate-missing --limit=1
```

### 로그 확인
```bash
# 성공 시 로그
tail -f storage/logs/laravel.log | grep "Thumbnail generated successfully"

# FFmpeg 없을 때 로그 (정상 동작)
tail -f storage/logs/laravel.log | grep "FFmpeg not installed"
```

## 참고사항

### FFmpeg 미설치 시 동작
- **에러 없이 정상 동작**: FFmpeg가 없어도 애플리케이션은 정상 작동
- **경고 로그만 기록**: `FFmpeg not installed on server - skipping video thumbnail generation`
- **수동 썸네일 업로드**: Admin 페이지에서 수동으로 썸네일 이미지 업로드 가능

### FFmpeg 설치 후 혜택
- **자동 썸네일 생성**: video 업로드 시 자동으로 1초 지점 썸네일 생성
- **일괄 생성 가능**: `php artisan thumbnails:generate-missing` 명령어로 기존 비디오도 썸네일 생성
- **품질 최적화**: 1280px 너비, 85% JPEG 품질로 자동 리사이즈

### 디스크 용량 확인
FFmpeg 설치는 약 100-200MB의 디스크 공간이 필요합니다:
```bash
# 디스크 용량 확인
df -h

# FFmpeg 패키지 크기 확인 (설치 전)
apt-cache show ffmpeg | grep Size
```

## 트러블슈팅

### 권한 문제
```bash
# PHP-FPM 사용자 확인
ps aux | grep php-fpm

# FFmpeg 실행 권한 확인
ls -l $(which ffmpeg)

# 필요 시 실행 권한 추가
sudo chmod +x /usr/bin/ffmpeg
```

### 경로 문제
PHP의 `exec()` 함수가 FFmpeg를 찾지 못하는 경우:
```bash
# PHP의 PATH 확인
php -r "echo getenv('PATH');"

# FFmpeg 전체 경로로 수정 (필요시)
# app/Jobs/GenerateVideoThumbnail.php 에서
# 'ffmpeg' → '/usr/bin/ffmpeg'
```

## 관련 파일
- `app/Jobs/GenerateVideoThumbnail.php`: 썸네일 생성 Job
- `app/Console/Commands/GenerateMissingVideoThumbnails.php`: 일괄 생성 명령어

## 추가 도움
FFmpeg 공식 문서: https://ffmpeg.org/documentation.html
