<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminArticleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = trim((string) $request->query('status'));

        $articles = Article::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('focus_keyword', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['draft', 'published'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.articles.index', compact('articles', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.articles.create', ['article' => new Article()]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $data['content'] = $this->sanitizeContent($data['content']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['published_at'] = $this->resolvePublishedAt($data);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = ImageOptimizer::storeOptimized(
                $request->file('featured_image'),
                'articles',
                1600,
                900,
                700 * 1024
            );
        }

        Article::create($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.edit', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validatedData($request, $article);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $article->id);
        $data['content'] = $this->sanitizeContent($data['content']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['published_at'] = $this->resolvePublishedAt($data, $article);

        if ($request->boolean('remove_image') && $article->featured_image) {
            MediaStore::delete($article->featured_image);
            $data['featured_image'] = null;
        }

        if ($request->hasFile('featured_image')) {
            if ($article->featured_image) {
                MediaStore::delete($article->featured_image);
            }

            $data['featured_image'] = ImageOptimizer::storeOptimized(
                $request->file('featured_image'),
                'articles',
                1600,
                900,
                700 * 1024
            );
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $article)
    {
        if ($article->featured_image) {
            MediaStore::delete($article->featured_image);
        }

        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'category' => ['nullable', 'string', 'max:100'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'focus_keyword' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'artikel';
        $slug = $base;
        $counter = 2;

        while (Article::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function resolvePublishedAt(array $data, ?Article $article = null)
    {
        if ($data['status'] !== 'published') {
            return null;
        }

        return $data['published_at'] ?: ($article?->published_at ?? now());
    }

    private function sanitizeContent(string $content): string
    {
        $allowedTags = '<p><br><h2><h3><h4><strong><b><em><i><u><ul><ol><li><blockquote><a><hr><table><thead><tbody><tr><th><td>';
        $content = preg_replace('/<div\b[^>]*>/i', '<p>', $content);
        $content = str_ireplace('</div>', '</p>', $content);
        $content = strip_tags($content, $allowedTags);

        $content = preg_replace_callback('/<a\b[^>]*>/i', function (array $matches) {
            $href = '#';

            if (preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $matches[0], $hrefMatch)) {
                $candidate = trim(html_entity_decode($hrefMatch[2], ENT_QUOTES, 'UTF-8'));
                if (preg_match('#^(https?://|mailto:|/|\#)#i', $candidate)) {
                    $href = $candidate;
                }
            }

            return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">';
        }, $content);

        return trim((string) preg_replace('/<(?!a\b)([a-z0-9]+)\b[^>]*>/i', '<$1>', $content));
    }
}
