@extends('layouts.app')

@section('content')
<div x-data="barcodeApp()">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">{{ config('app.name') }}</h1>
        <p class="text-slate-500">{{ __('messages.subtitle') }}</p>
    </header>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- ------------------------------------------------ Form --}}
        <form class="bg-white rounded-xl shadow p-6 space-y-4"
              method="POST" action="{{ route('barcode.download') }}"
              @submit="onDownload">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.type') }}</label>
                <select name="type" x-model="type" @change="queuePreview"
                        class="w-full border rounded-lg px-3 py-2">
                    @foreach ($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.code') }}</label>
                <input type="text" name="code" x-model="code" @input="queuePreview"
                       placeholder="{{ __('messages.code_ph') }}"
                       class="w-full border rounded-lg px-3 py-2 font-mono">
            </div>

            <div x-show="isAddon" x-cloak class="space-y-3 border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">{{ __('messages.addon') }}</label>
                    {{-- Toggle modalità --}}
                    <div class="inline-flex rounded-lg border border-slate-300 overflow-hidden text-sm">
                        <button type="button" @click="setAddonMode('guidato')"
                                :class="addonMode === 'guidato' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                                class="px-3 py-1">{{ __('messages.guided') }}</button>
                        <button type="button" @click="setAddonMode('diretto')"
                                :class="addonMode === 'diretto' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                                class="px-3 py-1 border-l border-slate-300">{{ __('messages.direct') }}</button>
                    </div>
                </div>

                {{-- Guidato: EAN-5 (prezzo libro) --}}
                <template x-if="addonMode === 'guidato' && type === 'EAN13+5'">
                    <div class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">{{ __('messages.currency') }}</label>
                                <select x-model="addonCurrency" @change="composeAddon()"
                                        class="w-full border rounded-lg px-2 py-2 text-sm">
                                    <option value="5">5 — USD $ ({{ __('messages.cur_usd') }})</option>
                                    <option value="6">6 — CAD $ ({{ __('messages.cur_cad') }})</option>
                                    <option value="1">1 — GBP £ ({{ __('messages.cur_gbp') }})</option>
                                    <option value="0">0 — GBP £ ({{ __('messages.cur_gbp') }})</option>
                                    <option value="3">3 — AUD $ ({{ __('messages.cur_aud') }})</option>
                                    <option value="4">4 — NZD $ ({{ __('messages.cur_nzd') }})</option>
                                    <option value="9">9 — {{ __('messages.cur_no_price') }} (90000)</option>
                                </select>
                            </div>
                            <div x-show="addonCurrency !== '9'">
                                <label class="block text-xs text-slate-500 mb-1">{{ __('messages.price') }}</label>
                                <input type="number" step="0.01" min="0" max="99.99"
                                       x-model="addonPrice" @input="composeAddon()"
                                       placeholder="24.95"
                                       class="w-full border rounded-lg px-2 py-2 text-sm font-mono">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">{{ __('messages.price_help') }}</p>
                    </div>
                </template>

                {{-- Guidato: EAN-2 (numero edizione) --}}
                <template x-if="addonMode === 'guidato' && type === 'EAN13+2'">
                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">{{ __('messages.issue') }}</label>
                            <input type="number" min="0" max="99"
                                   x-model="addonIssue" @input="composeAddon()"
                                   placeholder="7"
                                   class="w-full border rounded-lg px-2 py-2 text-sm font-mono">
                        </div>
                        <p class="text-xs text-slate-500">{{ __('messages.issue_help') }}</p>
                    </div>
                </template>

                {{-- Valore add-on effettivo (inviato col form) --}}
                <div>
                    <label class="block text-xs text-slate-500 mb-1"
                           x-text="addonMode === 'guidato' ? I18N.addon_generated : I18N.addon_code"></label>
                    <input type="text" name="addon" x-model="addon" @input="queuePreview"
                           :readonly="addonMode === 'guidato'"
                           :placeholder="type === 'EAN13+2' ? '07' : '52495'"
                           :class="addonMode === 'guidato' ? 'bg-slate-100 text-slate-500' : ''"
                           class="w-full border rounded-lg px-3 py-2 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.bar_width') }}</label>
                <select name="width" x-model="width" @change="queuePreview"
                        class="w-full border rounded-lg px-3 py-2">
                    <option value="fine">{{ __('messages.w_fine') }}</option>
                    <option value="medio">{{ __('messages.w_medium') }}</option>
                    <option value="largo">{{ __('messages.w_large') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('messages.bar_height') }}: <span x-text="height"></span>px</label>
                <input type="range" name="height" min="30" max="160" x-model="height" @input="queuePreview" class="w-full">
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="show_text" value="1" x-model="showText" @change="queuePreview">
                    <span class="text-sm">{{ __('messages.show_text') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <span class="text-sm">{{ __('messages.color') }}</span>
                    <input type="color" name="color" x-model="color" @input="queuePreview">
                </label>
            </div>

            <hr>

            <div>
                <label class="block text-sm font-medium mb-2">{{ __('messages.output_formats') }}</label>
                <div class="flex flex-wrap gap-3">
                    <template x-for="f in ['svg','eps','pdf','jpeg']" :key="f">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="formats[]" :value="f" x-model="formats">
                            <span class="uppercase text-sm" x-text="f"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div x-show="formats.includes('jpeg')" x-cloak>
                <label class="block text-sm font-medium mb-1">{{ __('messages.dpi') }}</label>
                <select name="dpi" x-model="dpi" class="w-full border rounded-lg px-3 py-2">
                    <option value="150">150</option>
                    <option value="300">300</option>
                    <option value="600">600</option>
                </select>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-2.5">
                {{ __('messages.download') }} (<span x-text="formats.length"></span>)
            </button>
        </form>

        {{-- ------------------------------------------------ Preview --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="font-semibold mb-3">{{ __('messages.preview') }}</h2>
                <div class="border rounded-lg p-4 bg-white min-h-[160px] flex items-center justify-center overflow-auto">
                    <template x-if="error">
                        <p class="text-red-600 text-sm" x-text="error"></p>
                    </template>
                    <div x-show="!error" x-html="svg"></div>
                </div>
            </div>

            {{-- Batch upload --}}
            <form class="bg-white rounded-xl shadow p-6 space-y-3"
                  method="POST" action="{{ route('barcode.batch') }}" enctype="multipart/form-data">
                @csrf
                <h2 class="font-semibold">{{ __('messages.batch_title') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.batch_cols') }}: <code>type,code,addon,output_format</code> &middot; {{ __('messages.batch_note') }}.</p>
                <input type="file" name="csv" accept=".csv,.txt" required
                       class="block w-full text-sm border rounded-lg p-2">
                <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg px-4 py-2">
                    {{ __('messages.batch_button') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const I18N = @json(__('messages'));
function barcodeApp() {
    return {
        type: 'ISBN13',
        code: '9788804707264',
        addon: '',
        addonMode: 'guidato',
        addonCurrency: '5',
        addonPrice: '',
        addonIssue: '',
        width: 'medio',
        height: 80,
        showText: true,
        color: '#000000',
        formats: ['svg'],
        dpi: '300',
        svg: '',
        error: '',
        _previewTimer: null,
        get isAddon() { return this.type === 'EAN13+2' || this.type === 'EAN13+5'; },
        init() { this.preview(); },
        // Coalesce rapid interactions (slider drag, typing) into one request
        // so we never hammer the rate-limited /generate endpoint.
        queuePreview() {
            clearTimeout(this._previewTimer);
            this._previewTimer = setTimeout(() => this.preview(), 300);
        },
        setAddonMode(mode) {
            this.addonMode = mode;
            if (mode === 'guidato') { this.composeAddon(); }
        },
        buildAddon() {
            if (this.type === 'EAN13+5') {
                if (this.addonCurrency === '9') {
                    this.addon = '90000';
                } else if (this.addonPrice !== '' && this.addonPrice !== null) {
                    let cents = Math.round(parseFloat(this.addonPrice) * 100);
                    if (isNaN(cents) || cents < 0) cents = 0;
                    if (cents > 9999) cents = 9999;
                    this.addon = this.addonCurrency + String(cents).padStart(4, '0');
                } else {
                    this.addon = '';
                }
            } else if (this.type === 'EAN13+2') {
                if (this.addonIssue !== '' && this.addonIssue !== null) {
                    let n = parseInt(this.addonIssue, 10);
                    if (isNaN(n) || n < 0) n = 0;
                    if (n > 99) n = 99;
                    this.addon = String(n).padStart(2, '0');
                } else {
                    this.addon = '';
                }
            }
        },
        composeAddon() {
            this.buildAddon();
            this.queuePreview();
        },
        async preview() {
            if (!this.code) { this.svg = ''; this.error = ''; return; }
            // Keep the guided add-on in sync (e.g. after switching type).
            if (this.isAddon && this.addonMode === 'guidato') { this.buildAddon(); }
            // While composing an add-on, wait for the full value before fetching.
            if (this.isAddon) {
                const need = this.type === 'EAN13+2' ? 2 : 5;
                if (!/^\d+$/.test(this.addon) || this.addon.length !== need) {
                    this.svg = '';
                    this.error = I18N.err_addon_digits.replace(':n', need);
                    return;
                }
            }
            try {
                const res = await fetch('{{ route('barcode.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: this.type, code: this.code, addon: this.addon,
                        width: this.width, height: this.height,
                        show_text: this.showText, color: this.color,
                    }),
                });
                if (res.status === 429) {
                    // Rate limited: keep the current preview and back off before retrying.
                    this.error = I18N.err_rate_limited;
                    clearTimeout(this._previewTimer);
                    this._previewTimer = setTimeout(() => this.preview(), 1500);
                    return;
                }
                const data = await res.json();
                if (data.ok) { this.svg = data.svg; this.error = ''; }
                else { this.error = data.error || I18N.err_validation; }
            } catch (e) {
                this.error = I18N.err_network;
            }
        },
        onDownload(e) {
            if (this.formats.length === 0) {
                e.preventDefault();
                this.error = I18N.err_no_format;
            }
        },
    };
}
</script>
@endpush
