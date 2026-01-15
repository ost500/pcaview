<?php

namespace App\Jobs;

use App\Models\Contents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Contents $content
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // file_url이 없거나 file_type이 video가 아니면 스킵
            if (! $this->content->file_url || $this->content->file_type !== 'video') {
                Log::info('Skipping thumbnail generation: not a video', ['content_id' => $this->content->id]);

                return;
            }

            // 이미 썸네일이 있으면 스킵
            if ($this->content->thumbnail_url) {
                Log::info('Thumbnail already exists', ['content_id' => $this->content->id]);

                return;
            }

            // FFmpeg 설치 여부 확인
            exec('which ffmpeg 2>&1', $ffmpegCheck, $ffmpegCheckCode);
            if ($ffmpegCheckCode !== 0) {
                Log::warning('FFmpeg not installed on server - skipping video thumbnail generation', [
                    'content_id' => $this->content->id,
                    'message' => 'Install FFmpeg to enable automatic video thumbnail generation',
                ]);

                return;
            }

            // S3에서 비디오 파일 다운로드
            $videoPath = parse_url($this->content->file_url, PHP_URL_PATH);
            if (! $videoPath || ! Storage::disk('s3')->exists($videoPath)) {
                Log::error('Video file not found', ['content_id' => $this->content->id, 'path' => $videoPath]);

                return;
            }

            // 임시 파일로 다운로드
            $tempVideoPath = storage_path('app/temp/'.basename($videoPath));
            if (! file_exists(dirname($tempVideoPath))) {
                mkdir(dirname($tempVideoPath), 0755, true);
            }

            $videoContent = Storage::disk('s3')->get($videoPath);
            file_put_contents($tempVideoPath, $videoContent);

            // FFmpeg로 썸네일 생성 (1초 지점)
            $tempThumbnailPath = storage_path('app/temp/thumb_'.basename($videoPath, '.mp4').'.jpg');

            $ffmpegCommand = sprintf(
                'ffmpeg -y -i %s -ss 00:00:01.000 -vframes 1 -q:v 2 %s 2>&1',
                escapeshellarg($tempVideoPath),
                escapeshellarg($tempThumbnailPath)
            );

            exec($ffmpegCommand, $output, $returnCode);

            if ($returnCode !== 0 || ! file_exists($tempThumbnailPath)) {
                Log::error('FFmpeg thumbnail generation failed', [
                    'content_id' => $this->content->id,
                    'return_code' => $returnCode,
                    'output' => $output,
                ]);

                // 임시 파일 정리
                if (file_exists($tempVideoPath)) {
                    unlink($tempVideoPath);
                }

                return;
            }

            // 썸네일 리사이즈 및 최적화 (Intervention Image)
            $manager = new ImageManager(new Driver());
            $image = $manager->read($tempThumbnailPath);
            $image->scale(width: 1280); // 너비 1280px로 리사이즈

            // S3에 업로드
            $thumbnailFilename = 'thumbnails/video_'.uniqid().'_'.basename($videoPath, '.mp4').'.jpg';
            Storage::disk('s3')->put($thumbnailFilename, (string) $image->encodeByPath($tempThumbnailPath, quality: 85), 'public');

            $thumbnailUrl = Storage::disk('s3')->url($thumbnailFilename);

            // Contents 업데이트
            $this->content->update([
                'thumbnail_url' => $thumbnailUrl,
            ]);

            Log::info('Thumbnail generated successfully', [
                'content_id' => $this->content->id,
                'thumbnail_url' => $thumbnailUrl,
            ]);

            // 임시 파일 정리
            if (file_exists($tempVideoPath)) {
                unlink($tempVideoPath);
            }
            if (file_exists($tempThumbnailPath)) {
                unlink($tempThumbnailPath);
            }
        } catch (\Exception $e) {
            Log::error('Video thumbnail generation failed', [
                'content_id' => $this->content->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
