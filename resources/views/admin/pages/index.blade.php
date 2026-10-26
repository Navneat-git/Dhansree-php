@extends('layouts.admin')

@section('title', 'Policy & Static Pages')
@section('page_title', 'Policy & Static Pages')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Policy &amp; Static Content Pages</h1>
            <p class="text-xs text-slate-500 mt-1">Manage all legal policies (Privacy, Shipping, Returns, Terms) and informational pages (About Us, Contact Us, FAQ).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" target="_blank"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                View Live Storefront
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500">Total Policy &amp; Content Pages</p>
                <span class="p-2 rounded-lg bg-slate-100 text-slate-600">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $pages->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Core legal policies &amp; static pages</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500">Live on Storefront</p>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-emerald-600 mt-2">{{ $pages->where('is_visible', true)->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Publicly accessible by customers</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500">Draft / Hidden</p>
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-amber-600 mt-2">{{ $pages->where('is_visible', false)->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Returns 404 until published</p>
        </div>
    </div>

    <!-- Pages List Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">All Policy &amp; Info Pages</h2>
                <p class="text-[11px] text-slate-500">Click &ldquo;Edit Page &amp; Sections&rdquo; on any policy to modify its clauses, refund timelines, shipping terms, or introduction.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/75 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Page Title &amp; Category</th>
                        <th class="px-5 py-3.5">Storefront URL</th>
                        <th class="px-5 py-3.5 text-center">Sections</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5">Last Updated</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-normal">
                    @forelse($pages as $p)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Title & Key -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900 text-sm">{{ $p->title }}</span>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $p->key instanceof \App\Enums\ContentPageKey ? $p->key->value : $p->key }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    <span>{{ $p->category_name }}</span>
                                    @if($p->seo_title)
                                        <span class="mx-1.5">&bull;</span>
                                        <span class="truncate max-w-xs inline-block align-bottom" title="{{ $p->seo_title }}">SEO: {{ $p->seo_title }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Storefront URL -->
                            <td class="px-5 py-4">
                                <a href="{{ $p->storefront_url }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-700 font-mono text-[11px] hover:underline">
                                    <span>{{ $p->storefront_path }}</span>
                                    <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            </td>

                            <!-- Sections count -->
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $p->content_page_sections_count }} {{ Str::plural('section', $p->content_page_sections_count) }}
                                </span>
                            </td>

                            <!-- Visibility Status & Toggle -->
                            <td class="px-5 py-4 text-center">
                                @if($canManage)
                                    <form method="POST" action="{{ route('admin.pages.toggle', $p->id) }}" class="inline-block">
                                        @csrf
                                        <button type="submit"
                                                title="Click to toggle visibility"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-colors cursor-pointer {{ $p->is_visible ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200' }}">
                                            <span class="size-1.5 rounded-full {{ $p->is_visible ? 'bg-emerald-600' : 'bg-rose-600' }}"></span>
                                            {{ $p->is_visible ? 'Live' : 'Hidden' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $p->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $p->is_visible ? 'Live' : 'Hidden' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Last Updated -->
                            <td class="px-5 py-4 text-slate-500 text-[11px]">
                                {{ $p->updated_at ? $p->updated_at->format('d M Y, h:i A') : '—' }}
                            </td>

                            <!-- Action Button -->
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.pages.edit', $p->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-2xs transition-colors">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                    Edit Page &amp; Sections
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                No policy pages found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
