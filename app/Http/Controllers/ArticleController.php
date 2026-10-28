<?php

namespace App\Http\Controllers;

use App\Services\ArticleApiClient;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request, ArticleApiClient $articleApi)
    {
        $articles = $articleApi->paginate([
            'page' => $request->integer('page', 1),
        ], 9);

        return view('articles.index', compact('articles'));
    }

    public function show(string $slug, ArticleApiClient $articleApi)
    {
        $result = $articleApi->find($slug);
        abort_unless($result, 404);

        $article = $result['article'];
        $relatedArticles = $result['related'];
        $latestArticles = $relatedArticles
            ->concat($articleApi->latest(8))
            ->reject(fn ($item) => $item->slug === $article->slug)
            ->unique('slug')
            ->take(5)
            ->values();

        return view('articles.show', compact('article', 'relatedArticles', 'latestArticles'));
    }
}
