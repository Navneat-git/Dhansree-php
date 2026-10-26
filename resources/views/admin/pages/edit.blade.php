@extends('layouts.admin')

@section('title', 'Edit ' . $page->title)
@section('page_title', 'Edit Policy Page')

@section('content')
<div class="space-y-8" x-data="{
    addModalOpen: false,
    editModalOpen: false,
    activeSection: { id: '', heading: '', body: '' },
    openEdit(section) {
        this.activeSection = {
            id: section.id,
            heading: section.heading,
            body: section.body || ''
        };
        this.editModalOpen = true;
    }
}">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1.5">
                <a href="{{ route('admin.pages.index') }}" class="hover:text-amber-600 transition-colors">Policy &amp; Static Pages</a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">{{ $page->title }}</span>
            </nav>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $page->title }}</h1>
                <span class="text-[11px] font-mono px-2.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $page->key instanceof \App\Enums\ContentPageKey ? $page->key->value : $page->key }}
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $page->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    <span class="size-1.5 rounded-full {{ $page->is_visible ? 'bg-emerald-600' : 'bg-rose-600' }}"></span>
                    {{ $page->is_visible ? 'Live on Store' : 'Draft / Hidden' }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ $page->storefront_url }}" target="_blank"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                View on Storefront
            </a>

            @if($canManage)
            <button type="button" @click="addModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase transition-colors shadow-2xs cursor-pointer">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Section
            </button>
            @endif
        </div>
    </div>

    <!-- Section 1: Page Details & SEO -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Page Settings &amp; Introduction</h2>
                <p class="text-[11px] text-slate-500">Configure page title, publish status, introductory preamble, and search engine meta tags.</p>
            </div>
            <span class="text-[10px] text-slate-400 font-mono">Version #{{ $page->version }}</span>
        </div>

        <form method="POST" action="{{ route('admin.pages.update', $page->id) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Page Title -->
                <div class="md:col-span-2 space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        Page Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required maxlength="70"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                           placeholder="e.g. Privacy policy">
                    <p class="text-[10px] text-slate-400">Displayed in page heading, breadcrumbs, and footer links (max 70 characters).</p>
                </div>

                <!-- Page Visibility Checkbox -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Visibility Status</label>
                    <div class="mt-2.5 flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', $page->is_visible) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                            <span class="ml-3 text-xs font-medium text-slate-700">Publish on Storefront</span>
                        </label>
                    </div>
                    <p class="text-[10px] text-slate-400">When disabled, this page will return a 404 error on the website.</p>
                </div>
            </div>

            <!-- Page Intro / Preamble -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700">Page Introduction / Preamble</label>
                    <span class="text-[10px] text-slate-400">Markdown supported (bold, italics, links, line breaks)</span>
                </div>
                <textarea name="intro" rows="5"
                          class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                          placeholder="e.g. Effective Date: 10 October 2026&#10;&#10;Dhansree, operated by Dhanshree Trends in Jaipur, Rajasthan, respects your privacy...">{{ old('intro', $page->intro) }}</textarea>
                <p class="text-[10px] text-slate-400">Appears highlighted in a warm box at the top of the policy page before individual sections.</p>
            </div>

            <!-- SEO Settings -->
            <div class="border-t border-slate-100 pt-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Search Engine Optimization (SEO)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-700">SEO Meta Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" maxlength="120"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                               placeholder="e.g. Privacy Policy | Dhansree Online Shopping">
                        <p class="text-[10px] text-slate-400">Recommended length: 50–60 characters. Max 120.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-700">SEO Meta Description</label>
                        <textarea name="seo_description" rows="2" maxlength="320"
                                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                                  placeholder="e.g. Read Dhansree's Privacy Policy to learn how we collect, use, and protect your personal information.">{{ old('seo_description', $page->seo_description) }}</textarea>
                        <p class="text-[10px] text-slate-400">Recommended length: 120–160 characters. Max 320.</p>
                    </div>
                </div>
            </div>

            @if($canManage)
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs tracking-wider uppercase shadow-2xs transition-colors cursor-pointer">
                    Save Page Settings
                </button>
            </div>
            @endif
        </form>
    </div>

    <!-- Section 2: Policy Clauses & Sections -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">
                    Policy Clauses &amp; Content Sections ({{ $page->contentPageSections->count() }})
                </h2>
                <p class="text-[11px] text-slate-500">Each section consists of a clear sub-heading and paragraph/bullet-point text. You can edit, reorder, add or remove sections at any time.</p>
            </div>

            @if($canManage)
            <button type="button" @click="addModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase transition-colors shadow-2xs cursor-pointer">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Section
            </button>
            @endif
        </div>

        <div class="p-6 space-y-4">
            @forelse($page->contentPageSections as $sec)
                <div class="border border-slate-200/80 rounded-xl p-4 sm:p-5 bg-white hover:border-amber-200 transition-colors shadow-2xs space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="size-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center shrink-0">
                                {{ $loop->iteration }}
                            </span>
                            <h3 class="font-bold text-slate-900 text-sm sm:text-base">{{ $sec->heading }}</h3>
                        </div>

                        @if($canManage)
                        <div class="flex items-center gap-1.5 self-end sm:self-auto">
                            <!-- Move Up -->
                            @if(!$loop->first)
                                <form method="POST" action="{{ route('admin.pages.sections.move', $page->id) }}">
                                    @csrf
                                    <input type="hidden" name="section_id" value="{{ $sec->id }}">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" title="Move Up" class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            <!-- Move Down -->
                            @if(!$loop->last)
                                <form method="POST" action="{{ route('admin.pages.sections.move', $page->id) }}">
                                    @csrf
                                    <input type="hidden" name="section_id" value="{{ $sec->id }}">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" title="Move Down" class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            <div class="h-4 w-px bg-slate-200 mx-1"></div>

                            <!-- Edit Section Button -->
                            <button type="button"
                                    @click="openEdit({{ json_encode($sec) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 text-xs font-semibold transition-colors cursor-pointer">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                                Edit
                            </button>

                            <!-- Delete Section Button -->
                            <form method="POST" action="{{ route('admin.pages.sections.destroy', $sec->id) }}" onsubmit="return confirm('Are you sure you want to delete the section \'{{ addslashes($sec->heading) }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center p-1.5 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer"
                                        title="Delete section">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Section Content Body Preview -->
                    <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/60 p-4 rounded-lg font-sans">
                        {{ $sec->body }}
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <svg class="size-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-700">No content sections yet</p>
                    <p class="text-xs text-slate-500 mt-1">Click &ldquo;Add New Section&rdquo; to add your first policy clause or guideline.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal: Add New Section -->
    <div x-show="addModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="addModalOpen = false">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-5"
             @click.away="addModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Add New Content Section</h3>
                <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.pages.sections.store', $page->id) }}" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        Section Heading <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="heading" required maxlength="120"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                           placeholder="e.g. Return eligibility conditions, Order dispatch timeline">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700">Section Content (Markdown &amp; Text)</label>
                        <span class="text-[10px] text-slate-400">Supports bullet points, links, bold, italics</span>
                    </div>
                    <textarea name="body" rows="8"
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"
                              placeholder="Write policy terms, rules, bullet points, or guidelines here..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="addModalOpen = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-2xs transition-colors cursor-pointer">
                        Add Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Existing Section -->
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="editModalOpen = false">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-5"
             @click.away="editModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Edit Content Section</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer text-lg font-bold">&times;</button>
            </div>

            <form method="POST" :action="'{{ url('/admin/pages/sections') }}/' + activeSection.id" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        Section Heading <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="heading" x-model="activeSection.heading" required maxlength="120"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700">Section Content (Markdown &amp; Text)</label>
                        <span class="text-[10px] text-slate-400">Supports bullet points, links, bold, italics</span>
                    </div>
                    <textarea name="body" x-model="activeSection.body" rows="10"
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:border-amber-500 focus:outline-none shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-2xs transition-colors cursor-pointer">
                        Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
