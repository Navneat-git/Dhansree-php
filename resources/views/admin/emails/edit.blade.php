@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    tab: 'html',
    testModalOpen: false,
    testEmail: '{{ $adminEmail ?? '' }}',
    testSending: false,
    testMessage: '',
    testSuccess: false,
    async sendTest() {
        if (!this.testEmail) return;
        this.testSending = true;
        this.testMessage = '';
        try {
            const res = await fetch('{{ url('/admin/emails/' . $typeName . '/test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ recipient: this.testEmail })
            });
            const data = await res.json();
            this.testSuccess = data.success;
            this.testMessage = data.message || (data.success ? 'Test email sent!' : 'Failed to send.');
        } catch (e) {
            this.testSuccess = false;
            this.testMessage = 'Network error while sending test email.';
        } finally {
            this.testSending = false;
        }
    }
}">

    <!-- Breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ url('/admin/emails') }}" class="hover:text-slate-800 transition-colors">Email Templates</a>
                <span>/</span>
                <span class="text-slate-800 font-medium">{{ str_replace('_', ' ', ucwords(strtolower($typeName), '_')) }}</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                {{ str_replace('_', ' ', ucwords(strtolower($typeName), '_')) }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                {{ $definition['description'] }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="testModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors shadow-xs">
                <span>✉️</span>
                <span>Send Test Email</span>
            </button>
            <form action="{{ url('/admin/emails/' . $typeName . '/reset') }}" method="POST" onsubmit="return confirm('Reset this template back to factory default words?')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-rose-600 text-xs font-semibold hover:bg-rose-50 transition-colors shadow-xs">
                    <span>Reset to Defaults</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            ✕ {{ session('error') }}
        </div>
    @endif

    <!-- Main Grid: Form on Left, Live Preview on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Form -->
        <div class="lg:col-span-6 space-y-6">
            <form action="{{ url('/admin/emails/' . $typeName) }}" method="POST" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 shadow-xs">
                @csrf
                @method('PUT')

                <!-- Subject -->
                <div class="space-y-1.5">
                    <label for="subject" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Subject Line <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="subject"
                           name="subject"
                           value="{{ old('subject', $template->subject) }}"
                           required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2 px-3">
                    <p class="text-[0.6875rem] text-slate-400">The subject displayed in the customer's inbox.</p>
                </div>

                <!-- Heading -->
                <div class="space-y-1.5">
                    <label for="heading" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Main Heading <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="heading"
                           name="heading"
                           value="{{ old('heading', $template->heading) }}"
                           required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2 px-3">
                    <p class="text-[0.6875rem] text-slate-400">Prominent headline at the top of the email.</p>
                </div>

                <!-- Intro -->
                <div class="space-y-1.5">
                    <label for="intro" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Intro Paragraph <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="intro"
                              name="intro"
                              rows="3"
                              required
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2 px-3">{{ old('intro', $template->intro) }}</textarea>
                    <p class="text-[0.6875rem] text-slate-400">The opening explanation sentence in your brand voice.</p>
                </div>

                <!-- Show Item Images -->
                @if(in_array($typeName, ['ORDER_CONFIRMATION', 'PAYMENT_CONFIRMATION']))
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox"
                           id="show_item_images"
                           name="show_item_images"
                           value="1"
                           {{ old('show_item_images', $template->show_item_images) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <label for="show_item_images" class="text-xs font-medium text-slate-700 cursor-pointer">
                        Include product thumbnail photographs in order item rows
                    </label>
                </div>
                @endif

                <!-- Placeholders Helper -->
                <div class="bg-slate-50 rounded-lg p-3.5 border border-slate-100 space-y-2">
                    <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500 block">
                        Available Dynamic Tokens
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;order_number&#125;&#125;</span>
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;customer_name&#125;&#125;</span>
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;customer_first_name&#125;&#125;</span>
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;total&#125;&#125;</span>
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;store_name&#125;&#125;</span>
                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[0.6875rem] font-mono text-slate-700">&#123;&#123;support_email&#125;&#125;</span>
                    </div>
                </div>

                <!-- Custom HTML Body (Optional) -->
                <div class="pt-2 border-t border-slate-100" x-data="{ openCustomHtml: {{ !empty($template->body_html) ? 'true' : 'false' }} }">
                    <button type="button"
                            @click="openCustomHtml = !openCustomHtml"
                            class="flex items-center justify-between w-full text-xs font-semibold text-slate-700 hover:text-slate-900 py-1">
                        <span>Advanced: Custom HTML Body (Optional)</span>
                        <span x-text="openCustomHtml ? '− Hide' : '+ Show'"></span>
                    </button>
                    <div x-show="openCustomHtml" class="mt-3 space-y-2">
                        <textarea name="body_html"
                                  rows="6"
                                  placeholder="Leave blank to use Dhansree's standard responsive card layout..."
                                  class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-3 leading-relaxed">{{ old('body_html', $template->body_html) }}</textarea>
                        <p class="text-[0.6875rem] text-slate-400">
                            When specified, your custom HTML replaces the inner body while preserving the legal footer and plain-text fallback.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ url('/admin/emails') }}" class="text-xs text-slate-500 hover:text-slate-800">
                        &larr; Back to Templates
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 transition-colors shadow-xs">
                        Save Template Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Preview Pane -->
        <div class="lg:col-span-6 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Live Preview</span>
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg text-xs">
                    <button type="button"
                            @click="tab = 'html'"
                            :class="tab === 'html' ? 'bg-white text-slate-900 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1 rounded-md transition-all">
                        HTML Email
                    </button>
                    <button type="button"
                            @click="tab = 'text'"
                            :class="tab === 'text' ? 'bg-white text-slate-900 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1 rounded-md transition-all">
                        Plain Text
                    </button>
                </div>
            </div>

            <!-- HTML Preview Container -->
            <div x-show="tab === 'html'" class="bg-slate-100 rounded-xl border border-slate-200 p-3 overflow-hidden shadow-xs">
                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                    <iframe srcdoc="{{ htmlspecialchars($preview['html'], ENT_QUOTES, 'UTF-8') }}"
                            class="w-full h-[620px] border-0"
                            title="HTML Email Preview"></iframe>
                </div>
            </div>

            <!-- Text Preview Container -->
            <div x-show="tab === 'text'" class="bg-slate-900 rounded-xl p-4 text-emerald-400 font-mono text-xs overflow-x-auto max-h-[620px] whitespace-pre-wrap leading-relaxed shadow-xs">
                {{ $preview['text'] }}
            </div>
        </div>

    </div>

    <!-- Send Test Modal -->
    <div x-cloak
         x-show="testModalOpen"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div @click.away="testModalOpen = false" class="bg-white rounded-xl border border-slate-200 p-6 max-w-md w-full space-y-4 shadow-xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Send Test Email</h3>
                <button type="button" @click="testModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <p class="text-xs text-slate-600">
                Send a live test of <strong>{{ str_replace('_', ' ', ucwords(strtolower($typeName), '_')) }}</strong> to your inbox to check styling in your mail client.
            </p>

            <div class="space-y-1.5">
                <label for="test_email" class="block text-xs font-semibold text-slate-700">Recipient Email:</label>
                <input type="email"
                       id="test_email"
                       x-model="testEmail"
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5">
            </div>

            <div x-show="testMessage"
                 :class="testSuccess ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
                 class="p-3 rounded-lg border text-xs"
                 x-text="testMessage"></div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button"
                        @click="testModalOpen = false"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button"
                        @click="sendTest()"
                        :disabled="testSending || !testEmail"
                        class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 disabled:opacity-50">
                    <span x-show="!testSending">Send Now</span>
                    <span x-show="testSending">Sending...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
