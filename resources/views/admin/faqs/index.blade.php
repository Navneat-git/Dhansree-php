@extends('layouts.admin')

@section('title', 'FAQs & Knowledge Base')

@section('content')
<div class="space-y-6" x-data="{
    activeGroupId: null,
    groupModalOpen: false,
    entryModalOpen: false,
    editingGroup: null,
    editingEntry: null,
    targetGroupId: null,
    openNewGroup() {
        this.editingGroup = null;
        this.groupModalOpen = true;
    },
    editGroup(grp) {
        this.editingGroup = grp;
        this.groupModalOpen = true;
    },
    openNewEntry(grpId) {
        this.editingEntry = null;
        this.targetGroupId = grpId;
        this.entryModalOpen = true;
    },
    editEntry(ent) {
        this.editingEntry = ent;
        this.targetGroupId = ent.group_id;
        this.entryModalOpen = true;
    }
}">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Frequently Asked Questions (FAQ)</h1>
            <p class="text-xs text-slate-500 mt-1">Manage storefront customer questions, delivery guidelines, return policies, and product sizing FAQs.</p>
        </div>
        @if($canManage)
        <button type="button"
                @click="openNewGroup()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs tracking-wider uppercase hover:bg-amber-400 transition-colors shadow-sm self-start sm:self-auto cursor-pointer">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add FAQ Group
        </button>
        @endif
    </div>

    <!-- Groups & Entries Accordion / Cards -->
    <div class="space-y-4">
        @if($groups->isEmpty() && (!isset($ungroupedEntries) || $ungroupedEntries->isEmpty()))
            <div class="bg-white rounded-xl border border-slate-200/80 p-12 text-center shadow-xs">
                <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No FAQ groups configured</h3>
                <p class="text-xs text-slate-500 mt-1">Group questions by topic like "Orders &amp; Shipping", "Returns &amp; Refunds", "Sizing &amp; Care".</p>
                @if($canManage)
                <div class="mt-4">
                    <button type="button" @click="openNewGroup()" class="px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs hover:bg-amber-400 transition-colors cursor-pointer">
                        Add First FAQ Group
                    </button>
                </div>
                @endif
            </div>
        @else
            @foreach($groups as $grp)
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden" x-data="{ expanded: true }">
                    <!-- Group Header -->
                    <div class="p-4 sm:p-5 bg-slate-50/75 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="expanded = !expanded" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                                <svg class="size-4.5 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-900 text-sm sm:text-base">{{ $grp->name }}</h3>
                                    <span class="text-[0.625rem] px-2 py-0.5 rounded-full font-semibold {{ $grp->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $grp->is_visible ? 'Visible' : 'Hidden' }}
                                    </span>
                                    <span class="text-[0.6875rem] text-slate-500 font-medium">({{ $grp->faqEntries->count() }} questions)</span>
                                </div>
                                <div class="flex items-center gap-2 text-[0.6875rem] text-slate-400 mt-0.5">
                                    <span>Slug: {{ $grp->slug }}</span>
                                    <span>•</span>
                                    <span>Pos: {{ $grp->position }}</span>
                                    @if($grp->show_on_faq_page) <span class="text-amber-600">• FAQ Page</span> @endif
                                    @if($grp->show_on_product) <span class="text-purple-600">• Product Page</span> @endif
                                </div>
                            </div>
                        </div>

                        @if($canManage)
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="openNewEntry({{ $grp->id }})"
                                    class="px-2.5 py-1.5 rounded-md bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors inline-flex items-center gap-1 cursor-pointer">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Question
                            </button>
                            <button type="button"
                                    @click="editGroup({{ json_encode($grp) }})"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-300 text-slate-700 text-xs font-medium hover:bg-slate-100 transition-colors cursor-pointer">
                                Edit Group
                            </button>
                            <form method="POST" action="{{ route('admin.faqs.groups.destroy', $grp->id) }}" class="inline" onsubmit="return confirm('Delete this FAQ group and all its questions?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-md transition-colors cursor-pointer" title="Delete Group">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Entries List -->
                    <div x-show="expanded" class="divide-y divide-slate-100">
                        @if($grp->faqEntries->isEmpty())
                            <div class="p-6 text-center text-xs text-slate-400 italic">
                                No questions in this group yet. Click "Add Question" above.
                            </div>
                        @else
                            @foreach($grp->faqEntries as $entry)
                                <div class="p-4 sm:p-5 hover:bg-slate-50/50 transition-colors flex items-start justify-between gap-4">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800">Q: {{ $entry->question }}</span>
                                            @if(!$entry->is_visible)
                                                <span class="text-[0.625rem] px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 font-semibold">Hidden</span>
                                            @endif
                                            @if($entry->category)
                                                <span class="text-[0.625rem] px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                                    Cat: {{ $entry->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $entry->answer }}</p>
                                    </div>

                                    @if($canManage)
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button"
                                                @click="editEntry({{ json_encode($entry) }})"
                                                class="px-2 py-1 rounded text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.faqs.entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('Delete this question?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition-colors cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        @if(!empty($ungroupedEntries) && $ungroupedEntries->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden" x-data="{ expanded: true }">
                <div class="p-4 sm:p-5 bg-slate-50/75 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="expanded = !expanded" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                            <svg class="size-4.5 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">General &amp; Ungrouped FAQs</h3>
                                <span class="text-[0.625rem] px-2 py-0.5 rounded-full font-semibold bg-blue-100 text-blue-800">
                                    Storefront Active
                                </span>
                                <span class="text-[0.6875rem] text-slate-500 font-medium">({{ $ungroupedEntries->count() }} questions)</span>
                            </div>
                            <p class="text-[0.6875rem] text-slate-400 mt-0.5">These questions appear directly on your customer FAQ page.</p>
                        </div>
                    </div>
                </div>

                <div x-show="expanded" class="divide-y divide-slate-100">
                    @foreach($ungroupedEntries as $entry)
                        <div class="p-4 sm:p-5 hover:bg-slate-50/50 transition-colors flex items-start justify-between gap-4">
                            <div class="space-y-1.5 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800">Q: {{ $entry->question }}</span>
                                    @if(!$entry->is_visible)
                                        <span class="text-[0.625rem] px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 font-semibold">Hidden</span>
                                    @endif
                                    <span class="text-[0.625rem] text-slate-400">#{{ $entry->id }} (pos: {{ $entry->position }})</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $entry->answer }}</p>
                            </div>

                            @if($canManage)
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button"
                                        @click="editEntry({{ json_encode($entry) }})"
                                        class="px-2 py-1 rounded text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.faqs.entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('Delete this question?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 rounded text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Group Create/Edit Modal -->
    <div x-show="groupModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="groupModalOpen = false">
            <h3 class="text-base font-bold text-slate-900" x-text="editingGroup ? 'Edit FAQ Group' : 'Add FAQ Group'"></h3>

            <form :action="editingGroup ? '{{ url('/admin/faqs/groups') }}/' + editingGroup.id : '{{ route('admin.faqs.groups.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editingGroup">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Group Name</label>
                    <input type="text" name="name" :value="editingGroup ? editingGroup.name : ''" required placeholder="e.g. Shipping &amp; Delivery" class="w-full text-xs rounded-lg border-slate-300">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Slug (Optional)</label>
                    <input type="text" name="slug" :value="editingGroup ? editingGroup.slug : ''" placeholder="e.g. shipping-delivery" class="w-full text-xs rounded-lg border-slate-300">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Position</label>
                    <input type="number" name="position" :value="editingGroup ? editingGroup.position : 0" min="0" class="w-full text-xs rounded-lg border-slate-300">
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_visible" value="1" :checked="!editingGroup || editingGroup.is_visible" class="rounded text-amber-500">
                        <span class="text-xs font-medium text-slate-800">Visible on Store</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_on_faq_page" value="1" :checked="!editingGroup || editingGroup.show_on_faq_page" class="rounded text-amber-500">
                        <span class="text-xs font-medium text-slate-800">Show on Main /faq Page</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_on_product" value="1" :checked="editingGroup && editingGroup.show_on_product" class="rounded text-amber-500">
                        <span class="text-xs font-medium text-slate-800">Show on Product Detail Pages</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" @click="groupModalOpen = false" class="px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-lg uppercase tracking-wider">
                        Save Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Entry Create/Edit Modal -->
    <div x-show="entryModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="entryModalOpen = false">
            <h3 class="text-base font-bold text-slate-900" x-text="editingEntry ? 'Edit Question & Answer' : 'Add Question & Answer'"></h3>

            <form :action="editingEntry ? '{{ url('/admin/faqs/entries') }}/' + editingEntry.id : '{{ url('/admin/faqs/groups') }}/' + targetGroupId + '/entries'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editingEntry">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Question</label>
                    <input type="text" name="question" :value="editingEntry ? editingEntry.question : ''" required placeholder="e.g. How do I track my order delivery?" class="w-full text-xs rounded-lg border-slate-300">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Answer</label>
                    <textarea name="answer" rows="4" required placeholder="Enter clear, helpful answer..." class="w-full text-xs rounded-lg border-slate-300" x-text="editingEntry ? editingEntry.answer : ''"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Optional Category</label>
                        <select name="category_id" class="w-full text-xs rounded-lg border-slate-300">
                            <option value="">None (Global FAQ)</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Position</label>
                        <input type="number" name="position" :value="editingEntry ? editingEntry.position : 0" min="0" class="w-full text-xs rounded-lg border-slate-300">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_visible" value="1" :checked="!editingEntry || editingEntry.is_visible" class="rounded text-amber-500">
                        <span class="text-xs font-medium text-slate-800">Visible to Customers</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" @click="entryModalOpen = false" class="px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-lg uppercase tracking-wider">
                        Save Question
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
