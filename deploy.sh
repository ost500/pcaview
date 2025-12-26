#!/bin/bash

# 배포 스크립트 - pcaview.com SSR 배포
# 사용법: bash deploy.sh

set -e  # 에러 발생 시 중단

echo "🚀 pcaview.com 배포 시작..."

# 1. Git 변경사항 확인
echo "📦 Git 상태 확인..."
git status

# 2. 변경사항 푸시
echo "⬆️  변경사항 푸시..."
git push origin main

# 3. 프로덕션 서버 배포
echo "🌐 프로덕션 서버 배포 중..."

ssh forge@pcaview.com << 'ENDSSH'
set -e

echo "📂 프로젝트 디렉토리로 이동..."
cd pcaview.com

echo "⬇️  최신 코드 가져오기..."
git pull origin main

echo "🧹 구 빌드 파일 완전 삭제..."
rm -rf public/build/*
rm -rf bootstrap/ssr/*

echo "📦 의존성 확인..."
npm ci --production=false

echo "🏗️  SSR 빌드..."
npm run build:ssr

echo "⚙️  SSR 환경변수 설정..."
grep -q "INERTIA_SSR_ENABLED" .env || echo "INERTIA_SSR_ENABLED=true" >> .env
grep -q "INERTIA_SSR_URL" .env || echo "INERTIA_SSR_URL=http://127.0.0.1:13714" >> .env

echo "🔧 Laravel 최적화..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 SSR 서버 재시작..."
php artisan inertia:stop-ssr
sleep 2
php artisan inertia:start-ssr

echo "⚙️  큐 워커 재시작..."
php artisan queue:restart

echo "✅ 배포 완료!"

# SSR 서버 상태 확인
echo "📊 SSR 서버 상태:"
ps aux | grep "inertia:start-ssr" | grep -v grep || echo "⚠️  SSR 서버가 실행되지 않았습니다!"

ENDSSH

echo ""
echo "✨ 배포가 완료되었습니다!"
echo ""
echo "🧪 테스트 URL:"
echo "   - 홈: https://pcaview.com/"
echo "   - 콘텐츠: https://pcaview.com/contents/201"
echo ""
echo "📝 확인 사항:"
echo "   1. 페이지가 정상 로드되는지"
echo "   2. 콘솔에 SSR 에러가 없는지"
echo "   3. 카카오톡 공유 시 OG 이미지가 표시되는지"
