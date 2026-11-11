@extends('layouts.app')

@section('title', 'Publish Maritime Advisory')
@section('header_title', 'Publish Advisory')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Publish Maritime Advisory</h1>
            <p class="text-xs text-slate-400">Broadcast critical port, meteorological, or regulatory notices to operators and customers</p>
        </div>
        <a href="{{ route('news.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition">
            Cancel
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('news.store') }}" class="space-y-4">
            @csrf

            <!-- Title -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Advisory Headline *</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Typhoon Warning: Container Operations Suspended at East Asian Terminals"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                @error('title') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Category *</label>
                    <select name="category" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Image URL -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Cover Image URL (Optional)</label>
                    <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://images.unsplash.com/..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    @error('image_url') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Summary -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Executive Summary (Short Excerpt) *</label>
                <textarea name="summary" rows="2" required placeholder="Brief 1-2 sentence overview shown in the directory and dashboard ticker..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">{{ old('summary') }}</textarea>
                @error('summary') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Content -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Advisory Content *</label>
                <textarea name="content" rows="8" required placeholder="Provide full details, affected ports, recommended routing actions, carrier guidelines..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">{{ old('content') }}</textarea>
                @error('content') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Breaking Flag -->
            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                    <input type="checkbox" name="is_breaking" value="1" {{ old('is_breaking') ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    <span>Mark as <strong>Breaking Emergency Alert</strong> (highlights banner in red across the platform)</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('news.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-500/20 transition">
                    Publish Advisory
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
