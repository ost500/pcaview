<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $churches = Church::all();
        $departments = Department::all();
        $contents = Contents::with('images')->orderBy('published_at', 'desc')->take(1000)->get();

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = false;

        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $xml->appendChild($urlset);

        // Homepage
        $this->addUrl($xml, $urlset, route('home'), 'daily', '1.0', now()->format('Y-m-d'));

        // Privacy Policy
        $this->addUrl($xml, $urlset, route('privacy-policy'), 'monthly', '0.5');

        // Church Index
        $this->addUrl($xml, $urlset, route('church'), 'weekly', '0.8');

        // Churches
        foreach ($churches as $church) {
            $lastmod = $church->updated_at
                ? (is_string($church->updated_at) ? $church->updated_at : $church->updated_at->format('Y-m-d'))
                : null;
            $this->addUrl($xml, $urlset, route('church.show', $church->id), 'monthly', '0.7', $lastmod);
        }

        // Department Index
        $this->addUrl($xml, $urlset, route('department'), 'weekly', '0.8');

        // Departments
        foreach ($departments as $department) {
            $lastmod = $department->updated_at
                ? (is_string($department->updated_at) ? $department->updated_at : $department->updated_at->format('Y-m-d'))
                : null;
            $this->addUrl($xml, $urlset, route('department.show', $department->id), 'daily', '0.8', $lastmod);
        }

        // Contents with images
        foreach ($contents as $content) {
            $lastmod = null;

            // Use updated_at if available, otherwise use published_at
            $dateToUse = $content->updated_at ?? $content->published_at;

            if ($dateToUse) {
                if (is_string($dateToUse)) {
                    // Convert string datetime to Y-m-d format
                    $lastmod = date('Y-m-d', strtotime($dateToUse));
                } else {
                    $lastmod = $dateToUse->format('Y-m-d');
                }
            }

            // Collect images for this content
            $images = [];

            // Add thumbnail as primary image
            if ($content->thumbnail_url) {
                $images[] = [
                    'loc' => $this->ensureAbsoluteUrl($content->thumbnail_url),
                    'title' => $content->title,
                    'caption' => $content->title . ' - ' . ($content->department?->name ?? '명성교회 주보고'),
                ];
            }

            // Add additional images (up to 3 more)
            if ($content->images) {
                foreach ($content->images->take(3) as $image) {
                    if ($image->file_url) {
                        $images[] = [
                            'loc' => $this->ensureAbsoluteUrl($image->file_url),
                            'title' => $content->title,
                            'caption' => $content->title,
                        ];
                    }
                }
            }

            // Higher priority (0.8) and daily crawl frequency for better indexing
            $this->addUrl($xml, $urlset, route('contents.show', $content->id), 'daily', '0.8', $lastmod, $images);
        }

        return response($xml->saveXML(), 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    private function addUrl(\DOMDocument $xml, \DOMElement $urlset, string $loc, string $changefreq, string $priority, ?string $lastmod = null, array $images = []): void
    {
        $url = $xml->createElement('url');

        $locElement = $xml->createElement('loc', htmlspecialchars($loc, ENT_XML1, 'UTF-8'));
        $url->appendChild($locElement);

        if ($lastmod) {
            $lastmodElement = $xml->createElement('lastmod', htmlspecialchars($lastmod, ENT_XML1, 'UTF-8'));
            $url->appendChild($lastmodElement);
        }

        $changefreqElement = $xml->createElement('changefreq', $changefreq);
        $url->appendChild($changefreqElement);

        $priorityElement = $xml->createElement('priority', $priority);
        $url->appendChild($priorityElement);

        // Add images for Naver and Google image search
        foreach ($images as $imageData) {
            $imageElement = $xml->createElement('image:image');

            $imageLocElement = $xml->createElement('image:loc', htmlspecialchars($imageData['loc'], ENT_XML1, 'UTF-8'));
            $imageElement->appendChild($imageLocElement);

            if (isset($imageData['title'])) {
                $imageTitleElement = $xml->createElement('image:title', htmlspecialchars($imageData['title'], ENT_XML1, 'UTF-8'));
                $imageElement->appendChild($imageTitleElement);
            }

            if (isset($imageData['caption'])) {
                $imageCaptionElement = $xml->createElement('image:caption', htmlspecialchars($imageData['caption'], ENT_XML1, 'UTF-8'));
                $imageElement->appendChild($imageCaptionElement);
            }

            $url->appendChild($imageElement);
        }

        $urlset->appendChild($url);
    }

    /**
     * Convert relative URLs to absolute URLs for sitemap
     * External URLs (http/https) are returned as-is
     */
    private function ensureAbsoluteUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Already absolute URL (external images like YouTube, Kakao)
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Convert relative URL to absolute (local storage images)
        return url($url);
    }
}
