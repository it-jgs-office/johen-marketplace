<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleApiClient
{
    public function paginate(array $filters = [], int $perPage = 9): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(12, $perPage));
        $parameters = array_filter([
            'q' => $filters['q'] ?? null,
            'featured' => $filters['featured'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'page' => $page,
            'per_page' => $perPage,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = $this->requestList($parameters);
        $items = collect($payload['data'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $this->mapArticle($item))
            ->values();

        return $this->makePaginator(
            $items,
            (int) data_get($payload, 'meta.total', $items->count()),
            (int) data_get($payload, 'meta.per_page', $perPage),
            (int) data_get($payload, 'meta.current_page', $page)
        );
    }

    public function latest(int $limit = 3): Collection
    {
        $payload = $this->requestList([
            'per_page' => $limit,
            'page' => 1,
        ]);

        return collect($payload['data'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $this->mapArticle($item))
            ->values();
    }

    /** @return array{article: Article, related: Collection}|null */
    public function find(string $slug): ?array
    {
        try {
            $payload = Cache::flexible(
                'article_api.detail.'.sha1($slug),
                [120, 600],
                function () use ($slug) {
                    $response = Http::acceptJson()
                        ->connectTimeout(3)
                        ->timeout($this->timeout())
                        ->get($this->endpoint('/artikel/'.rawurlencode($slug)));

                    if ($response->status() === 404) {
                        return null;
                    }

                    if (! $response->successful()) {
                        throw new \RuntimeException('Article API detail request failed with status '.$response->status().'.');
                    }

                    return $response->json();
                }
            );

            if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
                return null;
            }

            return [
                'article' => $this->mapArticle($payload['data']),
                'related' => collect($payload['related'] ?? [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => $this->mapArticle($item))
                    ->values(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Article API detail request failed.', [
                'slug' => $slug,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function allForSitemap(): Collection
    {
        return $this->allArticleData()
            ->map(fn (array $item) => $this->mapArticle($item))
            ->values();
    }

    private function allArticleData(): Collection
    {
        $parameters = ['sort' => 'newest', 'per_page' => 50, 'page' => 1];
        $firstPage = $this->requestList($parameters);
        $items = collect($firstPage['data'] ?? [])->filter(fn ($item) => is_array($item));
        $lastPage = max(1, (int) data_get($firstPage, 'meta.last_page', 1));

        for ($page = 2; $page <= $lastPage; $page++) {
            $parameters['page'] = $page;
            $payload = $this->requestList($parameters);
            $items = $items->concat(
                collect($payload['data'] ?? [])->filter(fn ($item) => is_array($item))
            );
        }

        return $items->values();
    }

    private function makePaginator(Collection $items, int $total, int $perPage, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    private function requestList(array $parameters): array
    {
        ksort($parameters);

        try {
            return Cache::flexible(
                'article_api.list.'.sha1(http_build_query($parameters)),
                [120, 600],
                function () use ($parameters) {
                    $response = Http::acceptJson()
                        ->connectTimeout(3)
                        ->timeout($this->timeout())
                        ->get($this->endpoint('/artikel'), $parameters);

                    if (! $response->successful()) {
                        throw new \RuntimeException('Article API list request failed with status '.$response->status().'.');
                    }

                    return $response->json() ?: [];
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Article API list request failed.', [
                'parameters' => $parameters,
                'exception' => $e->getMessage(),
            ]);

            return ['data' => [], 'meta' => []];
        }
    }

    private function mapArticle(array $data): Article
    {
        $seo = is_array($data['seo'] ?? null) ? $data['seo'] : [];
        $article = new Article();

        $article->forceFill([
            'id' => (int) ($data['id'] ?? 0),
            'title' => (string) ($data['title'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'featured_image' => $data['featured_image'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'quote' => $data['quote'] ?? null,
            'content' => $data['content'] ?? '',
            'meta_title' => $data['meta_title'] ?? ($seo['meta_title'] ?? null),
            'meta_description' => $data['meta_description'] ?? ($seo['meta_description'] ?? null),
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'status' => $data['status'] ?? 'published',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'published_at' => $data['published_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'api_image_url' => $data['image_url'] ?? null,
            'api_reading_time' => max(1, (int) ($data['reading_time'] ?? 1)),
            'source' => $data['source'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'documentation' => $data['documentation'] ?? [],
        ]);
        $article->exists = true;

        return $article;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.article_api.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.article_api.timeout', 10));
    }
}
