# PCAview

PCAview - 트렌딩 뉴스와 실시간 소식

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Vue 3 (TypeScript), Inertia.js
- **Styling**: TailwindCSS 4, Reka UI components
- **Build**: Vite, Laravel Wayfinder
- **Database**: MySQL
- **Testing**: Pest PHP

## Development

### Setup

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed
```

### Running

```bash
# Full development stack (recommended)
composer dev

# Individual services
php artisan serve      # Backend only
npm run dev            # Frontend only
php artisan queue:listen --tries=1  # Queue worker
php artisan pail --timeout=0        # Log viewer
```

### Testing

```bash
composer test
# or
php artisan test
```

### Code Quality

```bash
# Format PHP code
./vendor/bin/pint

# Lint and fix frontend code
npm run lint

# Format frontend code
npm run format
```

## Features

- 실시간 트렌드 뉴스 수집 및 표시
- 태그 기반 콘텐츠 관리
- 플랫폼 댓글 동기화 (Naver, Kakao 등)
- SEO 최적화
- OG 이미지 자동 생성

## License

MIT
