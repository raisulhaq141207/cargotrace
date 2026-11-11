@extends('layouts.app')

@section('title', $article->title)
@section('header_title', 'Maritime Advisory')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Back to All Advisories</span>
        </a>

        @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('news.destroy', $article) }}" onsubmit="return confirm('Are you sure you want to delete this advisory?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold transition">
                <i class="fa-solid fa-trash-can mr-1"></i> Delete
            </button>
        </form>
        @endif
    </div>

    <!-- Main Article Card -->
    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Hero Image (if available) -->
        @if($article->image_url)
        <div class="h-64 sm:h-80 w-full overflow-hidden bg-slate-900 relative">
            <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="w-full h-full object-cover opacity-90">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        </div>
        @endif

        <div class="p-6 sm:p-8 space-y-4">
            
            <!-- Metadata & Badges -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
                    {{ $article->category_label }}
                </span>
                @if($article->is_breaking)
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-600 text-white">
                    Breaking Alert
                </span>
                @endif
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-500">
                    <i class="fa-regular fa-clock mr-1"></i> Published {{ $article->published_at->format('F d, Y \a\t H:i') }} ({{ $article->published_at->diffForHumans() }})
                </span>
            </div>

            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug">
                {{ $article->title }}
            </h1>

            <!-- Summary Lead -->
            <div class="p-4 rounded-xl bg-slate-50 border-l-4 border-sky-500 text-slate-700 text-sm font-medium leading-relaxed">
                {{ $article->summary }}
            </div>

            <!-- Body Content -->
            <div class="prose max-w-none text-slate-700 text-sm leading-relaxed space-y-4 pt-2">
                {!! nl2br(e($article->content)) !!}
            </div>

            <!-- Author & Advisory Advisory Signoff -->
            <div class="pt-6 mt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                        CT
                    </div>
                    <div>
                        <div class="font-bold text-slate-700">CargoTrace Operational Intelligence Desk</div>
                        <div>Dispatched by {{ $article->author?->name ?? 'System Administrator' }}</div>
                    </div>
                </div>
                <div>
                    Ref: <code>ADV-{{ strtoupper(substr($article->slug, 0, 8)) }}</code>
                </div>
            </div>

        </div>

    </article>

    <!-- Related Advisories -->
    @if($relatedArticles->count() > 0)
    <div class="space-y-3 pt-4">
        <h3 class="text-sm font-bold text-slate-900">Related Advisories &amp; Notices</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($relatedArticles as $rel)
            <a href="{{ route('news.show', $rel->slug) }}" class="p-4 bg-white rounded-2xl border border-slate-200/80 hover:border-slate-300 shadow-sm transition block group">
                <span class="text-[10px] font-bold text-sky-600 uppercase">{{ $rel->category_label }}</span>
                <h4 class="font-bold text-xs text-slate-900 group-hover:text-sky-600 transition line-clamp-2 mt-1">{{ $rel->title }}</h4>
                <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">{{ $rel->summary }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

