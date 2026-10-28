@extends('layouts.admin')

@section('page_title', 'Instagram Feed & Lookbook')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'all',
    createModalOpen: false,
    editModalOpen: false,
    editingPost: null,
    openEdit(post) {
        this.editingPost = post;
        this.editModalOpen = true;
    }
}">
    <!-- Header Block -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center size-8 rounded-lg bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white shadow-xs">
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-900">Instagram Feed &amp; Lookbook</h1>
                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">
                    Live
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Manage Instagram reels, posts, and tagged products displayed on the homepage slider and dedicated <a href="{{ url('/instagram') }}" target="_blank" class="text-rose-600 underline font-medium hover:text-rose-700">/instagram</a> lookbook.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ url('/instagram') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors shadow-xs">
                <span>View Storefront Feed</span>
                <svg class="size-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </a>

            @if($canCreate)
            <button type="button" @click="createModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-gradient-to-r from-rose-600 to-amber-600 text-white hover:from-rose-700 hover:to-amber-700 transition-all shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add Reel / Post</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Items</span>
                <span class="size-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-sm">📸</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $posts->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $posts->where('is_visible', true)->count() }} active on storefront</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Reels</span>
                <span class="size-7 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 text-sm">🎬</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $posts->where('type', 'reel')->count() }}</p>
            <p class="text-[11px] text-rose-600 mt-0.5">High-engagement video format</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tagged Products</span>
                <span class="size-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-sm">🛍️</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $posts->whereNotNull('tagged_product_id')->count() }}</p>
            <p class="text-[11px] text-emerald-600 mt-0.5">Direct 1-click product purchase</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Likes</span>
                <span class="size-7 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 text-sm">❤️</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($posts->sum('likes_count')) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Social proof indicators</p>
        </div>
    </div>

    <!-- Filter Tabs Bar -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-3">
        <button type="button" @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 text-xs rounded-lg transition-colors cursor-pointer">
            All ({{ $posts->count() }})
        </button>
        <button type="button" @click="activeTab = 'reels'"
                :class="activeTab === 'reels' ? 'bg-rose-600 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 text-xs rounded-lg transition-colors cursor-pointer">
            🎬 Reels Only ({{ $posts->where('type', 'reel')->count() }})
        </button>
        <button type="button" @click="activeTab = 'posts'"
                :class="activeTab === 'posts' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 text-xs rounded-lg transition-colors cursor-pointer">
            📸 Regular Posts ({{ $posts->where('type', 'post')->count() }})
        </button>
        <button type="button" @click="activeTab = 'tagged'"
                :class="activeTab === 'tagged' ? 'bg-emerald-600 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 text-xs rounded-lg transition-colors cursor-pointer">
            🛍️ Shop The Look ({{ $posts->whereNotNull('tagged_product_id')->count() }})
        </button>
    </div>

    <!-- Items Table -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-14">Media</th>
                        <th class="py-3 px-4 w-24">Type</th>
                        <th class="py-3 px-4">Caption &amp; Link</th>
                        <th class="py-3 px-4">Tagged Product</th>
                        <th class="py-3 px-4 w-28">Stats</th>
                        <th class="py-3 px-4 w-20 text-center">Pos</th>
                        <th class="py-3 px-4 w-24 text-center">Status</th>
                        <th class="py-3 px-4 w-32 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($posts as $post)
                    <tr x-show="activeTab === 'all' || (activeTab === 'reels' && '{{ $post->type }}' === 'reel') || (activeTab === 'posts' && '{{ $post->type }}' === 'post') || (activeTab === 'tagged' && {{ $post->tagged_product_id ? 'true' : 'false' }})"
                        class="hover:bg-slate-50/70 transition-colors">
                        
                        <!-- Thumbnail Cover -->
                        <td class="py-3 px-4">
                            <div class="relative size-12 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                <img src="{{ $post->display_media_url }}" alt="Instagram media" class="w-full h-full object-cover">
                                @if($post->type === 'reel')
                                    <span class="absolute bottom-1 right-1 size-3.5 rounded bg-black/60 text-white flex items-center justify-center text-[8px]">▶</span>
                                @endif
                            </div>
                        </td>

                        <!-- Type Badge -->
                        <td class="py-3 px-4">
                            @if($post->type === 'reel')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span>🎬</span>
                                    <span>Reel</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    <span>📸</span>
                                    <span>Post</span>
                                </span>
                            @endif
                        </td>

                        <!-- Caption & Instagram Link -->
                        <td class="py-3 px-4 max-w-xs">
                            <p class="text-slate-900 font-medium line-clamp-2 leading-relaxed">
                                {{ $post->caption ?: 'No caption provided.' }}
                            </p>
                            <a href="{{ $post->display_instagram_url }}" target="_blank"
                               class="inline-flex items-center gap-1 text-[11px] text-rose-600 hover:text-rose-700 font-medium mt-1">
                                <span>{{ $post->instagram_url ? 'View on Instagram' : '@dhansreetrends' }}</span>
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                            </a>
                        </td>

                        <!-- Tagged Product -->
                        <td class="py-3 px-4">
                            @if($post->taggedProduct)
                                <a href="{{ url('/product/' . $post->taggedProduct->slug) }}" target="_blank"
                                   class="flex items-center gap-2 group max-w-xs">
                                    <img src="{{ $post->taggedProduct->primary_image_url }}" alt="" class="size-8 rounded object-cover border border-slate-200 shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-slate-900 truncate group-hover:text-amber-600">
                                            {{ $post->taggedProduct->name }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            {{ $post->taggedProduct->formatted_price }}
                                        </p>
                                    </div>
                                </a>
                            @else
                                <span class="text-slate-400 italic text-[11px]">None tagged</span>
                            @endif
                        </td>

                        <!-- Engagement Stats -->
                        <td class="py-3 px-4">
                            <div class="space-y-0.5 text-[11px]">
                                <div class="text-rose-600 font-semibold flex items-center gap-1">
                                    <span>❤️</span>
                                    <span>{{ $post->formatted_likes }}</span>
                                </div>
                                <div class="text-slate-500 flex items-center gap-1">
                                    <span>👁️</span>
                                    <span>{{ $post->formatted_views }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Position -->
                        <td class="py-3 px-4 text-center font-mono font-medium text-slate-600">
                            {{ $post->position }}
                        </td>

                        <!-- Visibility Toggle -->
                        <td class="py-3 px-4 text-center">
                            @if($canEdit)
                            <form method="POST" action="{{ route('admin.instagram.toggle', $post->id) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-colors {{ $post->is_visible ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}"
                                        title="Click to toggle visibility">
                                    <span class="size-1.5 rounded-full {{ $post->is_visible ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $post->is_visible ? 'Active' : 'Hidden' }}</span>
                                </button>
                            </form>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold {{ $post->is_visible ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $post->is_visible ? 'Active' : 'Hidden' }}
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($canEdit)
                                <button type="button"
                                        @click="openEdit({{ json_encode($post) }})"
                                        class="p-1.5 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                                        title="Edit Reel / Post">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                @endif

                                @if($canDelete)
                                <form method="POST" action="{{ route('admin.instagram.destroy', $post->id) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this Instagram reel/post?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-md text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition-colors"
                                            title="Delete">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-500">
                            <p class="text-base font-semibold text-slate-700">No Instagram posts found</p>
                            <p class="text-xs text-slate-400 mt-1">Click the button above to add your first Instagram reel or post.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Reel/Post Modal -->
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="createModalOpen = false"
             class="w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
            
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-rose-600 text-lg">📸</span>
                    <h3 class="text-sm font-bold text-slate-900">Add Instagram Reel or Post</h3>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.instagram.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Content Type</label>
                        <select name="type" required class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500">
                            <option value="reel">🎬 Instagram Reel (Vertical Video)</option>
                            <option value="post">📸 Regular Post (Photo/Carousel)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tagged Product (Shop The Look)</label>
                        <select name="tagged_product_id" class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500">
                            <option value="">-- None (No tagged product) --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Media (Cover / Photo)</label>
                    <input type="file" name="media" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                    <p class="text-[10px] text-slate-400 mt-1">Or provide direct URL below if already hosted.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Direct Media URL (Optional if uploaded)</label>
                    <input type="text" name="media_url" placeholder="e.g. products/2/6e2d12d1.webp or full CDN url"
                           class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Instagram URL</label>
                        <input type="url" name="instagram_url" placeholder="https://www.instagram.com/reel/..."
                               class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Video Stream/File URL (Optional)</label>
                        <input type="url" name="video_url" placeholder="https://.../video.mp4"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Caption &amp; Hashtags</label>
                    <textarea name="caption" rows="3" placeholder="Write caption with hashtags (#dhansreetrends #jaipurikurti...)"
                              class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Likes Count</label>
                        <input type="number" name="likes_count" value="1420" min="0"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Views Count</label>
                        <input type="text" name="views_count" value="22.5K"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Position / Order</label>
                        <input type="number" name="position" value="{{ $posts->count() + 1 }}" min="0"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_visible" value="1" checked class="rounded text-rose-600 focus:ring-rose-500">
                        <span>Visible on Storefront</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-xs">
                            Save Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Reel/Post Modal -->
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="editModalOpen = false"
             class="w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
             x-show="editingPost">
            
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-rose-600 text-lg">✏️</span>
                    <h3 class="text-sm font-bold text-slate-900" x-text="'Edit Instagram Item #' + (editingPost ? editingPost.id : '')"></h3>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'{{ url('/admin/instagram') }}/' + (editingPost ? editingPost.id : '')" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Content Type</label>
                        <select name="type" x-model="editingPost.type" required class="w-full text-xs rounded-lg border border-slate-300 p-2">
                            <option value="reel">🎬 Instagram Reel (Vertical Video)</option>
                            <option value="post">📸 Regular Post (Photo/Carousel)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tagged Product</label>
                        <select name="tagged_product_id" x-model="editingPost.tagged_product_id" class="w-full text-xs rounded-lg border border-slate-300 p-2">
                            <option value="">-- None (No tagged product) --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Replace Media File (Optional)</label>
                    <input type="file" name="media" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Media URL</label>
                    <input type="text" name="media_url" x-model="editingPost.media_url"
                           class="w-full text-xs rounded-lg border border-slate-300 p-2">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Instagram URL</label>
                        <input type="url" name="instagram_url" x-model="editingPost.instagram_url"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Video URL (Optional)</label>
                        <input type="url" name="video_url" x-model="editingPost.video_url"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Caption</label>
                    <textarea name="caption" rows="3" x-model="editingPost.caption"
                              class="w-full text-xs rounded-lg border border-slate-300 p-2"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Likes Count</label>
                        <input type="number" name="likes_count" x-model="editingPost.likes_count" min="0"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Views Count</label>
                        <input type="text" name="views_count" x-model="editingPost.views_count"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Position</label>
                        <input type="number" name="position" x-model="editingPost.position" min="0"
                               class="w-full text-xs rounded-lg border border-slate-300 p-2">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_visible" value="1" :checked="editingPost ? editingPost.is_visible : false" class="rounded text-rose-600 focus:ring-rose-500">
                        <span>Visible on Storefront</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-xs">
                            Update Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
