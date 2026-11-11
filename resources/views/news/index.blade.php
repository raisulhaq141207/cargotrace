@extends('layouts.app')

@section('title', 'Maritime News & Advisories')
@section('header_title', 'Maritime News')

@section('content')
<div class="space-y-6">

    <!-- Top Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Maritime News &amp; Trade Advisories</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 uppercase">Global Intel</span>
            </h1>
            <p class="text-xs text-slate-400">Real-time port congestion notices, meteorological alerts, carrier surcharges &amp; regulatory updates</p>
        </div>

        @if(!auth()->user()->isCustomer())
        <a href="{{ route('news.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold shadow-md shadow-sky-500/20 transition">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Publish Advisory</span>
        </a>
        @endif
    </div>

    <!-- Breaking News Alert Banner (if any) -->
    @if($breakingNews)
    <div class="bg-gradient-to-r from-rose-500 to-rose-600 rounded-2xl p-4 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded-lg bg-white/20 backdrop-blur-sm text-[10px] font-extrabold uppercase tracking-widest flex items-center gap-1.5 shrink-0">
                <span class="w-2 h-2 rounded-full bg-white animate-ping"></span> Breaking Alert
            </span>
            <div class="min-w-0">
                <a href="{{ route('news.show', $breakingNews->slug) }}" class="font-bold text-sm hover:underline line-clamp-1 text-white">
                    {{ $breakingNews->title }}
                </a>
                <p class="text-xs text-rose-100 line-clamp-1 mt-0.5">{{ $breakingNews->summary }}</p>
            </div>
        </div>
        <a href="{{ route('news.show', $breakingNews->slug) }}" class="px-3 py-1.5 rounded-xl bg-white text-rose-600 hover:bg-rose-50 text-xs font-bold transition shrink-0 self-start md:self-center">
            Read Advisory &rarr;
        </a>
    </div>
    @endif

    <!-- Search & Filter Category Pills -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <!-- Search input -->
        <form method="GET" action="{{ route('news.index') }}" class="flex gap-2">
            @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search advisories, port notices, carrier regulations..."
                    class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                Search
            </button>
            @if(request()->hasAny(['search', 'category']))
            <a href="{{ route('news.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                Reset
            </a>
            @endif
        </form>

        <!-- Category Pills -->
        <div class="flex flex-wrap items-center gap-1.5 pt-1 border-t border-slate-100 text-xs">
            <a href="{{ route('news.index', request()->only('search')) }}"
                class="px-3 py-1 rounded-xl text-xs font-semibold transition {{ !request('category') ? 'bg-[#13354a] text-sky-400' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Topics
            </a>
            @foreach($categories as $key => $label)
            <a href="{{ route('news.index', array_merge(request()->only('search'), ['category' => $key])) }}"
                class="px-3 py-1 rounded-xl text-xs font-semibold transition {{ request('category') === $key ? 'bg-[#13354a] text-sky-400' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- News Articles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($newsList as $item)
        @php
            $catColors = [
                'PORT_ADVISORY'  => 'bg-blue-50 text-blue-700 border-blue-200',
                'WEATHER_ALERT'  => 'bg-amber-50 text-amber-700 border-amber-200',
                'REGULATORY'     => 'bg-purple-50 text-purple-700 border-purple-200',
                'CARRIER_UPDATE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'TRADE_LANE'     => 'bg-sky-50 text-sky-700 border-sky-200',
            ];
            $badgeClass = $catColors[$item->category] ?? 'bg-slate-50 text-slate-700 border-slate-200';
        @endphp
        <article class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between hover:border-slate-300 hover:shadow-md transition group">
            
            <!-- Thumbnail Image -->
            @if($item->image_url)
            <div class="h-44 w-full overflow-hidden bg-slate-100 relative">
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                <div class="absolute top-3 left-3 flex gap-1.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border backdrop-blur-md bg-white/90 {{ $badgeClass }}">
                        {{ $item->category_label }}
                    </span>
                    @if($item->is_breaking)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white shadow-sm">
                        Breaking
                    </span>
                    @endif
                </div>
            </div>
            @endif

            <!-- Body -->
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    @if(!$item->image_url)
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                            {{ $item->category_label }}
                        </span>
                        @if($item->is_breaking)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white">
                            Breaking
                        </span>
                        @endif
                    </div>
                    @endif

                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mb-2">
                        <span><i class="fa-regular fa-calendar mr-1"></i> {{ $item->published_at->format('M d, Y') }}</span>
                        <span>•</span>
                        <span>{{ $item->published_at->diffForHumans() }}</span>
                    </div>

                    <h2 class="text-sm font-bold text-slate-900 group-hover:text-sky-600 transition line-clamp-2 leading-snug">
                        <a href="{{ route('news.show', $item->slug) }}">
                            {{ $item->title }}
                        </a>
                    </h2>

                    <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
                        {{ $item->summary }}
                    </p>
                </div>

                <!-- Footer link -->
                <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400 text-[11px]">{{ ceil(str_word_count($item->content) / 200) }} min read</span>
                    <a href="{{ route('news.show', $item->slug) }}" class="font-bold text-sky-600 hover:text-sky-700 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition">
                        <span>Read Advisory</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

        </article>
        @empty
        <div class="col-span-3 py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
            <i class="fa-solid fa-newspaper text-4xl text-slate-300 mb-2"></i>
            <p class="text-sm font-semibold text-slate-700">No maritime advisories found.</p>
            <p class="text-xs text-slate-400 mt-1">Try clearing your search query or selecting another category.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div>
        {{ $newsList->links() }}
    </div>

</div>
@endsection
