# Google Trends Service

Google Trends RSS 데이터를 가져오고 파싱하는 서비스입니다.

## 사용법

### 기본 사용

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
        $trends = $this->trendsService->fetchTopTrends(10);

        return response()->json([
            'trends' => $this->trendsService->trendsToArray($trends),
        ]);
    }
}
```

## Artisan 커맨드 예시

```php
use App\Domain\trend\GoogleTrendsService;
use Illuminate\Console\Command;

class FetchTrendsCommand extends Command
{
    protected $signature = 'trends:fetch';
    protected $description = 'Fetch Google Trends data';

    public function handle(GoogleTrendsService $service): int
    {
        $this->info('Fetching Google Trends...');

        $trends = $service->fetchTrends();

        $this->table(
            ['Title', 'Traffic', 'Pub Date'],
            array_map(fn($t) => [
                $t->title,
                $t->trafficCount ?? 'N/A',
                $t->pubDate->format('Y-m-d H:i'),
            ], $trends)
        );

        $this->info('Total: ' . count($trends) . ' trends');

        return self::SUCCESS;
    }
}
```

## 테스트

```bash
php artisan test --filter=GoogleTrendsServiceTest
```

## 에러 처리

서비스는 다음과 같은 경우 빈 배열을 반환합니다:
- 네트워크 에러
- XML 파싱 실패
- HTTP 응답 실패

모든 에러는 Laravel 로그에 기록됩니다.
