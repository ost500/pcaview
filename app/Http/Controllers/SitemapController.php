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
        $contents = Contents::orderBy('published_at', 'desc')->take(1000)->get();

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = false;

        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);

        // Homepage
        $this->addUrl($xml, $urlset, route('home'), 'daily', '1.0', now()->toIso8601String());

        // Church Index
        $this->addUrl($xml, $urlset, route('church'), 'weekly', '0.8');

        // Churches
        foreach ($churches as $church) {
            $lastmod = $church->updated_at
                ? (is_string($church->updated_at) ? $church->updated_at : $church->updated_at->toIso8601String())
                : null;
            $this->addUrl($xml, $urlset, route('church.show', $church->id), 'monthly', '0.7', $lastmod);
        }

        // Department Index
        $this->addUrl($xml, $urlset, route('department'), 'weekly', '0.8');

        // Departments
        foreach ($departments as $department) {
            $lastmod = $department->updated_at
                ? (is_string($department->updated_at) ? $department->updated_at : $department->updated_at->toIso8601String())
                : null;
            $this->addUrl($xml, $urlset, route('department.show', $department->id), 'daily', '0.8', $lastmod);
        }

        // Contents
        foreach ($contents as $content) {
            $lastmod = $content->published_at
                ? (is_string($content->published_at) ? $content->published_at : $content->published_at->toIso8601String())
                : null;
            $this->addUrl($xml, $urlset, route('contents.show', $content->id), 'weekly', '0.6', $lastmod);
        }

        return response($xml->saveXML(), 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    private function addUrl(\DOMDocument $xml, \DOMElement $urlset, string $loc, string $changefreq, string $priority, ?string $lastmod = null): void
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

        $urlset->appendChild($url);
    }
}
