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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= '<url>';
        $xml .= '<loc>' . route('home') . '</loc>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '<lastmod>' . now()->format('c') . '</lastmod>';
        $xml .= '</url>';

        // Church Index
        $xml .= '<url>';
        $xml .= '<loc>' . route('church') . '</loc>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.8</priority>';
        $xml .= '</url>';

        // Churches
        foreach ($churches as $church) {
            $xml .= '<url>';
            $xml .= '<loc>' . route('church.show', $church->id) . '</loc>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            if ($church->updated_at) {
                $lastmod = is_string($church->updated_at)
                    ? $church->updated_at
                    : $church->updated_at->format('c');
                $xml .= '<lastmod>' . $lastmod . '</lastmod>';
            }
            $xml .= '</url>';
        }

        // Department Index
        $xml .= '<url>';
        $xml .= '<loc>' . route('department') . '</loc>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.8</priority>';
        $xml .= '</url>';

        // Departments
        foreach ($departments as $department) {
            $xml .= '<url>';
            $xml .= '<loc>' . route('department.show', $department->id) . '</loc>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.8</priority>';
            if ($department->updated_at) {
                $lastmod = is_string($department->updated_at)
                    ? $department->updated_at
                    : $department->updated_at->format('c');
                $xml .= '<lastmod>' . $lastmod . '</lastmod>';
            }
            $xml .= '</url>';
        }

        // Contents
        foreach ($contents as $content) {
            $xml .= '<url>';
            $xml .= '<loc>' . route('contents.show', $content->id) . '</loc>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            if ($content->published_at) {
                $lastmod = is_string($content->published_at)
                    ? $content->published_at
                    : $content->published_at->format('c');
                $xml .= '<lastmod>' . $lastmod . '</lastmod>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }
}
