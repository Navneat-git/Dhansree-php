@extends('layouts.admin')

@section('title', 'Homepage Banners')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Homepage Banners</h1>
            <p class="text-xs text-slate-500 mt-1">Manage promotional hero banners, sale announcements, and mobile sliders.</p>
        </div>
        @if($canCreate)
        <a href="{{ route('admin.banners.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs tracking-wider uppercase hover:bg-amber-400 transition-colors shadow-sm self-start sm:self-auto">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Create Banner
        </a>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Banners</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Now</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Scheduled</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['scheduled']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Expired</p>
            <p class="text-2xl font-bold text-slate-400 mt-1">{{ number_format($stats['expired']) }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.banners.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search title, eyebrow, or subtitle..."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>

            <div class="w-36">
                <select name="is_active" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    <option value="">All Statuses</option>
                    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(!empty($filters['search']) || isset($filters['is_active']))
                <a href="{{ route('admin.banners.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 text-xs hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Banners List Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($banners->isEmpty())
            <div class="p-12 text-center">
                <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No banners found</h3>
                <p class="text-xs text-slate-500 mt-1">Create your first homepage promotional hero banner.</p>
                @if($canCreate)
                <div class="mt-4">
                    <a href="{{ route('admin.banners.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs hover:bg-amber-400 transition-colors">
                        Create Banner
                    </a>
                </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-600 font-semibold uppercase tracking-wider text-[0.6875rem]">
                            <th class="py-3 px-4 w-12 text-center">Pos</th>
                            <th class="py-3 px-4 w-32">Preview</th>
                            <th class="py-3 px-4">Banner Details</th>
                            <th class="py-3 px-4">Link / CTA</th>
                            <th class="py-3 px-4">Schedule</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($banners as $banner)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 text-center font-bold text-slate-500">
                                    {{ $banner->position }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($banner->image_url)
                                        <img src="{{ $banner->image_url }}"
                                             alt="{{ $banner->image_alt_text ?? 'Banner' }}"
                                             class="w-24 h-12 object-cover rounded-md border border-slate-200">
                                    @else
                                        <div class="w-24 h-12 rounded-md bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center text-[0.625rem] text-slate-400">
                                            Text Only
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 space-y-0.5">
                                    @if($banner->eyebrow)
                                        <span class="inline-block text-[0.625rem] font-bold text-amber-600 uppercase tracking-wider">
                                            {{ $banner->eyebrow }}
                                        </span>
                                    @endif
                                    <div class="font-semibold text-slate-900 text-sm">
                                        {{ $banner->title ?: 'Untitled Banner' }}
                                    </div>
                                    @if($banner->subtitle)
                                        <p class="text-slate-500 text-xs line-clamp-1">{{ $banner->subtitle }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    @if($banner->link_path)
                                        <code class="text-[0.6875rem] bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">{{ $banner->link_path }}</code>
                                        @if($banner->cta_label)
                                            <div class="text-[0.6875rem] text-amber-700 font-medium mt-0.5">CTA: "{{ $banner->cta_label }}"</div>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">None</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[0.6875rem] text-slate-500">
                                    @if($banner->starts_at || $banner->ends_at)
                                        <div>From: {{ $banner->starts_at ? $banner->starts_at->format('d M Y') : 'Immediate' }}</div>
                                        <div>To: {{ $banner->ends_at ? $banner->ends_at->format('d M Y') : 'Always' }}</div>
                                    @else
                                        <span class="text-slate-500">Always Visible</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($canUpdate)
                                        <form method="POST" action="{{ route('admin.banners.toggle', $banner->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.6875rem] font-semibold transition-colors cursor-pointer {{ $banner->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                                <span class="size-1.5 rounded-full mr-1.5 {{ $banner->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                {{ $banner->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold {{ $banner->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                    @if($canUpdate)
                                    <a href="{{ route('admin.banners.edit', $banner->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium transition-colors">
                                        Edit
                                    </a>
                                    @endif
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50 font-medium transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($paginator->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $paginator->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
