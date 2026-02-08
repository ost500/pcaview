<?php

namespace App\Console\Commands;

use App\Domain\parkgolf\KpgaParkGolfService;
use App\Models\ParkGolfCourse;
use Illuminate\Console\Command;

class FetchParkGolfData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parkgolf:fetch {--search= : 특정 이름으로 검색} {--region= : 특정 지역으로 검색}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'KPGA(대한파크골프협회) 웹사이트에서 파크골프장 데이터를 가져와서 DB에 저장합니다';

    /**
     * Execute the console command.
     */
    public function handle(KpgaParkGolfService $service)
    {
        $this->info('파크골프장 데이터 수집 시작...');

        $searchName = $this->option('search');
        $region = $this->option('region');

        try {
            // 데이터 가져오기
            if ($searchName) {
                $this->info("'{$searchName}' 검색 중...");
                $parkGolfs = $service->searchByName($searchName);
            } elseif ($region) {
                $this->info("'{$region}' 지역 데이터 가져오는 중...");
                $parkGolfs = $service->fetchByRegion($region);
            } else {
                $this->info('전체 파크골프장 데이터 가져오는 중...');
                $this->warn('이 작업은 수 분이 걸릴 수 있습니다...');
                $parkGolfs = $service->fetchAllParkGolfs();
            }

            if (empty($parkGolfs)) {
                $this->warn('가져온 데이터가 없습니다.');
                return 0;
            }

            $this->info('총 ' . count($parkGolfs) . '개의 파크골프장 데이터를 가져왔습니다.');

            // 프로그레스 바 시작
            $bar = $this->output->createProgressBar(count($parkGolfs));
            $bar->start();

            $newCount = 0;
            $updatedCount = 0;

            foreach ($parkGolfs as $data) {
                // 이름과 지역으로 중복 체크
                $parkGolf = ParkGolfCourse::where('name', $data['name'])
                    ->where('region', $data['region'])
                    ->first();

                if (!$parkGolf) {
                    // 새로 생성
                    ParkGolfCourse::create([
                        'name' => $data['name'],
                        'region' => $data['region'],
                        'address' => $data['address'],
                        'area' => $data['area'],
                        'holes' => $data['holes'],
                        'detail_url' => $data['detail_url'],
                    ]);
                    $newCount++;
                } else {
                    // 기존 데이터 업데이트
                    $updated = false;

                    if ($parkGolf->address !== $data['address'] ||
                        $parkGolf->area !== $data['area'] ||
                        $parkGolf->holes !== $data['holes'] ||
                        $parkGolf->detail_url !== $data['detail_url']) {

                        $parkGolf->update([
                            'address' => $data['address'],
                            'area' => $data['area'],
                            'holes' => $data['holes'],
                            'detail_url' => $data['detail_url'],
                        ]);
                        $updatedCount++;
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ 완료!");
            $this->table(
                ['구분', '개수'],
                [
                    ['새로 추가', $newCount],
                    ['업데이트', $updatedCount],
                    ['총 처리', count($parkGolfs)],
                ]
            );

            // 지역별 통계
            $regionStats = collect($parkGolfs)->groupBy('region')->map(fn($items) => count($items));
            $this->newLine();
            $this->info('📊 지역별 통계:');
            foreach ($regionStats as $region => $count) {
                $this->line("  {$region}: {$count}개");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('에러 발생: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
