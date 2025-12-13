<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CmsController extends Controller
{
    public function page(string $slug)
    {

        $cacheKey = "cms_page_{$slug}";
        
        $page = Cache::remember($cacheKey, 3600, function () use ($slug) {
            try {

                $result = DB::selectOne(
                    "SELECT id, slug, title, content, is_active, created_at 
                     FROM cms_blocks 
                     WHERE slug = ? AND is_active = TRUE 
                     LIMIT 1",
                    [$slug]
                );
                
                return $result;
            } catch (\Exception $e) {
                Log::error('CMS query error', [
                    'slug' => $slug,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
        
        if (!$page) {

            return response()->view('cms.notfound', [
                'slug' => $slug,
                'message' => 'Страница не найдена'
            ], 404);
        }
        

        return response()->view('cms.page', [
            'page' => $page,
            'title' => e($page->title),
            'safe_content' => $this->sanitizeHtml($page->content)
        ]);
    }
    

    private function sanitizeHtml(string $html): string
    {

        $allowedTags = [
            'div', 'span', 'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'strong', 'em', 'b', 'i', 'u', 'strike', 'sub', 'sup',
            'ul', 'ol', 'li', 'dl', 'dt', 'dd',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
            'a', 'img', 'blockquote', 'pre', 'code', 'cite',
            'abbr', 'acronym', 'address', 'big', 'small', 'tt'
        ];
        
        $allowedAttributes = [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class', 'style'],
            'div' => ['class', 'style', 'id'],
            'span' => ['class', 'style'],
            'table' => ['class', 'style', 'border', 'cellpadding', 'cellspacing'],
            'td' => ['class', 'style', 'colspan', 'rowspan'],
            'th' => ['class', 'style', 'colspan', 'rowspan']
        ];
        

        $purifier = new \App\Support\HtmlPurifier();
        return $purifier->clean($html, $allowedTags, $allowedAttributes);
    }
}
