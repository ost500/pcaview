# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **AI 이미지 생성 확률 대폭 감소**: 프로덕션 환경에서 AI 이미지 생성 확률을 50% → 10%로 낮춤
  - 로컬/개발 환경은 여전히 100% 유지
  - API 비용 80% 절감 효과

- **AI 댓글 생성 확률 조정**: 30% → 50%로 상향
  - 이미지보다 댓글이 비용이 적게 들어 확률 증가
  - 콘텐츠 활성화를 위한 댓글 생성 빈도 증가

### Fixed
- **콘텐츠 중복 저장 방지**: `title` 기반 중복 체크 로직 추가
  - `Contents::create()` → `Contents::firstOrCreate()` 로 변경
  - 동일한 제목의 콘텐츠가 중복 저장되지 않도록 개선
  - 적용 범위:
    - 네이버 뉴스 크롤링 (`NaverNewsContentService`)
    - 네이트 뉴스 크롤링 (`NateNewsContentService`)
    - 뉴송제이 크롤링 (`NewsongJCrawlService`)
    - 밝은소리 크롤링 (`BrightSoriCrawlService`)
    - 주보 크롤링 (`JuboCrawlService`)
    - 엔프렌즈 크롤링 (`NFriendsCrawlService`)

## [Previous Releases]

### 2026-02-05
- AI 이미지 생성 확률 프로덕션 50%로 조정
- AI 관련 로직을 `AiNewsProcessingService`로 분리
- AI 댓글 생성 개수를 랜덤(1-5개)으로 변경
- Trend당 AI 이미지 1개만 생성하도록 제한
- AI로 뉴스 댓글 자동 생성 기능 추가

### Technical Details

#### AI Processing Probabilities
```php
// Before
private const IMAGE_GENERATION_PROBABILITY_PROD = 50;
private const COMMENT_GENERATION_PROBABILITY    = 30;

// After
private const IMAGE_GENERATION_PROBABILITY_PROD = 10;  // 80% 비용 절감
private const COMMENT_GENERATION_PROBABILITY    = 50;  // 댓글 활성화
```

#### Duplicate Content Prevention
```php
// Before
Contents::create([
    'title' => $title,
    // ... other fields
]);

// After
Contents::firstOrCreate(
    ['title' => $title],  // Duplicate check by title only
    [/* other fields */]  // Only saved if not duplicate
);
```

### Impact
- **긍정적 영향**:
  - **AI 이미지 생성 비용 80% 절감** (50% → 10%)
  - AI 댓글로 콘텐츠 활성화 증가 (30% → 50%)
  - 데이터베이스 저장 공간 절약 (중복 콘텐츠 제거)
  - 크롤링 성능 향상 (중복 체크 최적화)

- **비용 최적화 전략**:
  - 이미지 생성: 비용이 높아 10%로 제한
  - 댓글 생성: 비용이 낮아 50%로 증가
  - 전체 AI API 비용 대폭 감소

- **주의사항**:
  - 기존 중복 데이터는 자동으로 제거되지 않음
  - 향후 크롤링부터 중복 방지 적용
  - AI 이미지가 없는 콘텐츠 증가 (90%)
