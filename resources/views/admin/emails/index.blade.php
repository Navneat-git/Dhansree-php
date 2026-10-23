@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Transactional Email Templates</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Manage the wording, branding, and layouts of emails automatically sent to customers.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                <span>Mailer: {{ config('mail.default', 'smtp') }}</span>
            </span>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✕</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Email Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($templates as $item)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">{{ $item['name'] }}</h2>
                            @if($item['has_custom_html'])
                                <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[0.625rem] font-bold uppercase tracking-wider">
                                    Custom HTML
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[0.625rem] font-bold uppercase tracking-wider">
                                    Standard
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            {{ $item['description'] }}
                        </p>
                    </div>
                </div>

                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 text-xs space-y-1.5">
                    <div>
                        <span class="text-slate-400 font-medium">Subject:</span>
                        <span class="font-semibold text-slate-800 ml-1">{{ $item['subject'] }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Heading:</span>
                        <span class="text-slate-700 ml-1">{{ $item['heading'] }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[0.6875rem] text-slate-400">
                    {{ $item['updated_at'] ? 'Updated ' . $item['updated_at']->diffForHumans() : 'Built-in default' }}
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ url('/admin/emails/' . $item['type']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors shadow-xs">
                        <span>Edit Template</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Informational Card -->
    <div class="bg-amber-50/50 rounded-xl border border-amber-200/80 p-5 space-y-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-900 flex items-center gap-2">
            <span>ℹ️</span>
            <span>How Transactional Emails Work</span>
        </h3>
        <p class="text-xs text-amber-800 leading-relaxed">
            All customer emails are automatically formatted with Dhansree's royal maroon and gold brand palette, responsive 600px mobile-first layout, and full plain-text companion views. You can customize the subject line, heading, opening intro, or provide full custom HTML templates. If custom wording is saved, dynamic tokens such as <code class="bg-amber-100 px-1 py-0.5 rounded text-amber-900">&#123;&#123;order_number&#125;&#125;</code> and <code class="bg-amber-100 px-1 py-0.5 rounded text-amber-900">&#123;&#123;customer_name&#125;&#125;</code> are automatically replaced with live order facts.
        </p>
    </div>
</div>
@endsection
