# Google Trends Service

Google Trends RSS 데이터를 가져오고 파싱하며 데이터베이스에 저장하는 서비스입니다.

## 데이터베이스 스키마

```sql
CREATE TABLE trends (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    link VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    traffic_count INT,
    pub_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(title, pub_date)
);
```

## 사용법

### RSS 데이터 가져와서 DB에 저장

```php
use App\Domain\trend\GoogleTrendsService;

$service = new GoogleTrendsService();

// 트렌드 데이터 가져와서 데이터베이스에 저장
$count = $service->fetchAndSave();
echo "Saved {$count} trends";
```

### 데이터베이스에서 조회

```php
use App\Domain\trend\GoogleTrendsService;

$service = new GoogleTrendsService();

// 최신 10개 트렌드
$trends = $service->getLatestFromDatabase(10);

// 오늘의 트렌드
$todayTrends = $service->getTodayFromDatabase();

// 검색
$results = $service->search('키워드');
```

### RSS에서 직접 가져오기 (DB 저장 없이)

```php
use App\Domain\trend\GoogleTrendsService;

$service = new GoogleTrendsService();

// 전체 트렌드 가져오기
$trends = $service->fetchTrends();

foreach ($trends as $trend) {
    echo $trend->title . "\n";
    echo $trend->link . "\n";
    echo $trend->trafficCount . "\n";
}
```

### 상위 N개만 가져오기

```php
// 상위 5개만 가져오기
$topTrends = $service->fetchTopTrends(5);
```

### 트래픽 순으로 정렬

```php
$trends = $service->fetchTrends();
$sorted = $service->sortByTraffic($trends);
```

### 배열로 변환

```php
$trends = $service->fetchTrends();
$trendsArray = $service->trendsToArray($trends);

// JSON으로 변환
$json = json_encode($trendsArray);
```

## TrendItem 구조

```php
class TrendItem
{
    public readonly string $title;              // 트렌드 제목
    public readonly string $description;        // 설명
    public readonly string $link;               // Google Trends 링크
    public readonly \DateTimeImmutable $pubDate; // 발행 시간
    public readonly ?string $imageUrl;          // 이미지 URL (있는 경우)
    public readonly ?int $trafficCount;         // 트래픽 카운트 (있는 경우)
}
```

## 컨트롤러 사용 예시

```php
use App\Domain\trend\GoogleTrendsService;
use Illuminate\Http\JsonResponse;

class TrendController extends Controller
{
    public function __construct(
        private GoogleTrendsService $trendsService
    ) {}

    public function index(): JsonResponse
    {
        $trends = $this->trendsService->****fetchTopTrends(10);

        return response()->json([
            'trends' => $this->trendsService->trendsToArray($trends),
        ]);
    }
}
```

## Eloquent 모델 사용

```php
use App\Models\Trend;

// 최신 트렌드 10개
$trends = Trend::latest()->limit(10)->get();

// 트래픽 순으로 정렬
$popularTrends = Trend::byTraffic()->limit(10)->get();

// 오늘의 트렌드
$todayTrends = Trend::today()->get();

// 특정 기간의 트렌드
$trends = Trend::betweenDates('2025-11-01', '2025-11-30')->get();

// 검색
$results = Trend::where('title', 'like', '%키워드%')->get();
```

## Repository 패턴 사용

```php
use App\Domain\trend\TrendRepository;

$repository = new TrendRepository();

// 최신 트렌드
$trends = $repository->getLatest(10);

// 오늘의 트렌드
$todayTrends = $repository->getToday();

// 특정 기간
$trends = $repository->getBetweenDates('2025-11-01', '2025-11-30');

// 오래된 데이터 삭제 (30일 이전)
$deleted = $repository->deleteOlderThan(30);
```

## Artisan 커맨드 예시

```php
use App\Domain\trend\GoogleTrendsService;
use Illuminate\Console\Command;

class FetchTrendsCommand extends Command
{
    protected $signature = 'trends:fetch';
    protected $description = 'Fetch and save Google Trends data';

    public function handle(GoogleTrendsService $service): int
    {
        $this->info('Fetching Google Trends...');

        $count = $service->fetchAndSave();

        $this->info("Saved {$count} trends to database");

        // 저장된 데이터 표시
        $trends = $service->getLatestFromDatabase(10);

        $this->table(
            ['Title', 'Traffic', 'Pub Date'],
            $trends->map(fn($t) => [
                $t->title,
                $t->traffic_count ?? 'N/A',
                $t->pub_date->format('Y-m-d H:i'),
            ])
        );

        return self::SUCCESS;
    }
}
```

## 테스트

```bash
# GoogleTrendsService 테스트
php artisan test --filter=GoogleTrendsServiceTest

# TrendRepository 테스트
php artisan test --filter=TrendRepositoryTest

# 모든 Trend 관련 테스트
php artisan test --filter=Trend
```

## 스케줄링

정기적으로 트렌드 데이터를 업데이트하려면 `app/Console/Kernel.php`에 추가:

```php
protected function schedule(Schedule $schedule)
{
    // 1시간마다 트렌드 업데이트
    $schedule->call(function () {
        $service = new GoogleTrendsService();
        $service->fetchAndSave();
    })->hourly();

    // 매일 자정에 오래된 데이터 삭제 (30일 이전)
    $schedule->call(function () {
        $repository = new TrendRepository();
        $repository->deleteOlderThan(30);
    })->daily();
}
```

## 에러 처리

서비스는 다음과 같은 경우 빈 배열을 반환합니다:
- 네트워크 에러
- XML 파싱 실패
- HTTP 응답 실패

모든 에러는 Laravel 로그에 기록됩니다.

## 주요 기능

- ✅ Google Trends RSS 자동 파싱
- ✅ 데이터베이스 저장 및 중복 방지
- ✅ 트래픽 카운트 기반 정렬
- ✅ 날짜별 필터링 (오늘, 특정 기간)
- ✅ 키워드 검색
- ✅ 오래된 데이터 자동 정리
- ✅ Eloquent 스코프 지원
- ✅ Repository 패턴
- ✅ 완전한 테스트 커버리지
