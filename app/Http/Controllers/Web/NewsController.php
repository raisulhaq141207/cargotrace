<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsController extends Controller
{
    /**
     * Display maritime news and industry advisories directory.
     */
    public function index(Request $request): View
    {
        $query = News::published()->latest('published_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $breakingNews = News::published()->where('is_breaking', true)->latest('published_at')->first();
        $newsList = $query->paginate(9)->withQueryString();
        $categories = News::CATEGORIES;

        return view('news.index', compact('newsList', 'breakingNews', 'categories'));
    }

    /**
     * Display single news article detail.
     */
    public function show(string $slug): View
    {
        $article = News::published()->where('slug', $slug)->firstOrFail();
        $relatedArticles = News::published()
            ->where('id', '!=', $article->id)
            ->where('category', $article->category)
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = News::published()
                ->where('id', '!=', $article->id)
                ->latest('published_at')
                ->take(3)
                ->get();
        }

        return view('news.show', compact('article', 'relatedArticles'));
    }

    /**
     * Show the form for creating a new maritime advisory.
     */
    public function create(): View
    {
        $categories = News::CATEGORIES;
        return view('news.create', compact('categories'));
    }

    /**
     * Store a newly created advisory.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'in:' . implode(',', array_keys(News::CATEGORIES))],
            'summary'     => ['required', 'string', 'max:500'],
            'content'     => ['required', 'string'],
            'image_url'   => ['nullable', 'url', 'max:500'],
            'is_breaking' => ['nullable', 'boolean'],
        ]);

        $validated['author_id']   = Auth::id();
        $validated['slug']        = Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['is_breaking'] = $request->boolean('is_breaking');
        $validated['is_published'] = true;
        $validated['published_at'] = now();

        $news = News::create($validated);

        return redirect()->route('news.show', $news->slug)
            ->with('success', 'Maritime advisory published successfully.');
    }

    /**
     * Remove the specified advisory.
     */
    public function destroy(News $news): RedirectResponse
    {
        $news->delete();
        return redirect()->route('news.index')->with('success', 'Advisory deleted successfully.');
    }
}

