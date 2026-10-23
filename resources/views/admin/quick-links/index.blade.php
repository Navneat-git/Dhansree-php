@extends('layouts.admin')

@section('title', 'Quick Links Management')

@section('content')
<div class="space-y-6" x-data="{
    groupModalOpen: false,
    linkModalOpen: false,
    editingGroup: null,
    editingLink: null,
    targetGroupId: null,
    openNewGroup() {
        this.editingGroup = null;
        this.groupModalOpen = true;
    },
    editGroup(grp) {
        this.editingGroup = grp;
        this.groupModalOpen = true;
    },
    openNewLink(grpId) {
        this.editingLink = null;
        this.targetGroupId = grpId;
        this.linkModalOpen = true;
    },
    editLink(link) {
        this.editingLink = link;
        this.targetGroupId = link.group_id;
        this.linkModalOpen = true;
    }
}">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Quick Links Management</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure navigation chips and quick link groups displayed on Homepage, Category listing pages, Collection pages, and Product detail pages.
            </p>
        </div>
        @if($canManage)
        <button type="button"
                @click="openNewGroup()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs tracking-wider uppercase hover:bg-amber-400 transition-colors shadow-sm self-start sm:self-auto cursor-pointer">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Quick Link Group
        </button>
        @endif
    </div>

    <!-- Groups & Links Cards -->
    <div class="space-y-4">
        @if($groups->isEmpty())
            <div class="bg-white rounded-xl border border-slate-200/80 p-12 text-center shadow-xs">
                <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No Quick Link Groups created yet</h3>
                <p class="text-xs text-slate-500 mt-1">Create groups of links (e.g., "Trending Categories", "Popular Collections") to surface on your store.</p>
                @if($canManage)
                <div class="mt-4">
                    <button type="button" @click="openNewGroup()" class="px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs hover:bg-amber-400 transition-colors cursor-pointer">
                        Add First Quick Link Group
                    </button>
                </div>
                @endif
            </div>
        @else
            @foreach($groups as $group)
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 bg-slate-50/50">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-base font-bold text-slate-900">{{ $group->title }}</h2>
                                @if($group->is_visible)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">Hidden</span>
                                @endif

                                @if($group->show_on_home)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Home Page</span>
                                @endif
                                @if($group->show_on_listing)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-800 border border-sky-200">Category/Collection Listings</span>
                                @endif
                                @if($group->show_on_product)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-800 border border-purple-200">Product Details</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">
                                Position: <strong>{{ $group->position }}</strong> &bull; {{ $group->quickLinks->count() }} {{ Str::plural('Link', $group->quickLinks->count()) }}
                            </p>
                        </div>

                        @if($canManage)
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="openNewLink({{ $group->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-2xs transition-colors">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Link
                            </button>
                            <button type="button"
                                    @click="editGroup({{ json_encode($group) }})"
                                    class="px-2.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition-colors">
                                Edit Group
                            </button>
                            <form method="POST" action="{{ route('admin.quick-links.groups.destroy', $group->id) }}" onsubmit="return confirm('Are you sure you want to delete this Quick Link group and all its links?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 font-semibold text-xs transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Individual Links Table -->
                    @if($group->quickLinks->isEmpty())
                        <div class="p-8 text-center text-xs text-slate-400">
                            No links in this group yet. Click "Add Link" to add your first navigation pill.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-4 w-16">Pos</th>
                                        <th class="py-2.5 px-4">Link Label</th>
                                        <th class="py-2.5 px-4">Target Path / URL</th>
                                        <th class="py-2.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($group->quickLinks as $link)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-2.5 px-4 font-mono font-bold text-slate-400 text-center">{{ $link->position }}</td>
                                            <td class="py-2.5 px-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                                    {{ $link->label }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-4 font-mono text-slate-600">
                                                <a href="{{ url($link->path) }}" target="_blank" class="hover:text-amber-600 underline">
                                                    {{ $link->path }}
                                                </a>
                                            </td>
                                            <td class="py-2.5 px-4 text-right space-x-2">
                                                @if($canManage)
                                                <button type="button"
                                                        @click="editLink({{ json_encode($link) }})"
                                                        class="text-xs text-slate-600 hover:text-amber-600 font-medium transition-colors">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('admin.quick-links.links.destroy', $link->id) }}" class="inline" onsubmit="return confirm('Delete this link?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium transition-colors">
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
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- MODAL: ADD / EDIT GROUP -->
    <div x-cloak x-show="groupModalOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" @click="groupModalOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-5" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-base font-bold text-slate-900" x-text="editingGroup ? 'Edit Quick Link Group' : 'New Quick Link Group'"></h3>
                    <button type="button" @click="groupModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form method="POST" :action="editingGroup ? '{{ url('/admin/quick-links/groups') }}/' + editingGroup.id : '{{ route('admin.quick-links.groups.store') }}'" class="space-y-4">
                    @csrf
                    <template x-if="editingGroup">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Group Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required maxlength="60"
                               :value="editingGroup ? editingGroup.title : ''"
                               placeholder="e.g. Shop by Category, Popular Searches, Curated Collections"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Display Position</label>
                        <input type="number" name="position" min="0"
                               :value="editingGroup ? editingGroup.position : 0"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div class="space-y-2 pt-2 border-t border-slate-200">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_visible" value="1"
                                   :checked="!editingGroup || editingGroup.is_visible"
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-medium text-slate-700">Group is Active / Visible</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="show_on_home" value="1"
                                   :checked="editingGroup ? editingGroup.show_on_home : false"
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-medium text-slate-700">Display on Home Page</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="show_on_listing" value="1"
                                   :checked="editingGroup ? editingGroup.show_on_listing : false"
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-medium text-slate-700">Display on Category &amp; Collection Listing Pages</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="show_on_product" value="1"
                                   :checked="editingGroup ? editingGroup.show_on_product : false"
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-medium text-slate-700">Display on Product Detail Pages</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                        <button type="button" @click="groupModalOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-bold text-xs hover:bg-amber-400">Save Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT LINK -->
    <div x-cloak x-show="linkModalOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" @click="linkModalOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-5" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-base font-bold text-slate-900" x-text="editingLink ? 'Edit Quick Link' : 'Add Quick Link'"></h3>
                    <button type="button" @click="linkModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form method="POST" :action="editingLink ? '{{ url('/admin/quick-links/links') }}/' + editingLink.id : '{{ url('/admin/quick-links/groups') }}/' + targetGroupId + '/links'" class="space-y-4">
                    @csrf
                    <template x-if="editingLink">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Link Label <span class="text-rose-500">*</span></label>
                        <input type="text" name="label" required maxlength="60"
                               :value="editingLink ? editingLink.label : ''"
                               placeholder="e.g. Women Fashion, Kurta Sets, Co-ord Sets"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Path / URL <span class="text-rose-500">*</span></label>
                        <input type="text" name="path" required maxlength="200"
                               :value="editingLink ? editingLink.path : ''"
                               placeholder="e.g. /women-fashion or /kurta-set or /smart-living"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Display Position</label>
                        <input type="number" name="position" min="0"
                               :value="editingLink ? editingLink.position : 0"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                        <button type="button" @click="linkModalOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-800">Save Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
