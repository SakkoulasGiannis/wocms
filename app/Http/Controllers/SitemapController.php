<?php

namespace App\Http\Controllers;

use App\Models\ContentNode;
use Illuminate\Http\Response;

/**
 * Generates /sitemap.xml and /robots.txt from the published content tree,
 * honouring each entry's seo_sitemap_* settings where available.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $nodes = ContentNode::query()
            ->where('is_published', true)
            ->whereNotNull('url_path')
            ->orderBy('url_path')
            ->get(['url_path', 'content_type', 'content_id', 'updated_at']);

        $urls = [];
        foreach ($nodes as $node) {
            $model = $this->contentModel($node);
            if ($model && (int) ($model->seo_sitemap_include ?? 1) === 0) {
                continue;
            }

            $isHome = $node->url_path === '/';
            $urls[] = [
                'loc' => url($node->url_path),
                'lastmod' => optional($model?->updated_at ?? $node->updated_at)->toAtomString(),
                'changefreq' => $model->seo_sitemap_changefreq ?? ($isHome ? 'daily' : 'weekly'),
                'priority' => $isHome ? '1.0' : ($model->seo_sitemap_priority ?? '0.5'),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>".e($u['loc'])."</loc>\n";
            if ($u['lastmod']) {
                $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            }
            $xml .= '    <changefreq>'.e($u['changefreq'])."</changefreq>\n"
                .'    <priority>'.e($u['priority'])."</priority>\n  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\n"
            ."Disallow: /admin\n"
            ."Disallow: /login\n\n"
            .'Sitemap: '.url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function contentModel(ContentNode $node): ?\Illuminate\Database\Eloquent\Model
    {
        $class = $node->content_type;
        if (! $class || ! $node->content_id || ! class_exists($class)) {
            return null;
        }

        return $class::find($node->content_id);
    }
}
