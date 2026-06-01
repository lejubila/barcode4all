<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Barcode Generator Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="max-w-5xl mx-auto p-6" x-data="barcodeApp()">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Barcode Generator Pro</h1>
        <p class="text-slate-500">Genera barcode professionali in formato vettoriale (SVG, EPS, PDF, JPEG).</p>
    </header>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- ------------------------------------------------ Form --}}
        <form class="bg-white rounded-xl shadow p-6 space-y-4"
              method="POST" action="{{ route('barcode.download') }}"
              @submit="onDownload">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Tipo barcode</label>
                <select name="type" x-model="type" @change="queuePreview"
                        class="w-full border rounded-lg px-3 py-2">
                    @foreach ($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Codice</label>
                <input type="text" name="code" x-model="code" @input="queuePreview"
                       placeholder="es. 9788804707264"
                       class="w-full border rounded-lg px-3 py-2 font-mono">
            </div>

            <div x-show="isAddon" x-cloak class="space-y-3 border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Add-on (supplemento)</label>
                    {{-- Toggle modalità --}}
                    <div class="inline-flex rounded-lg border border-slate-300 overflow-hidden text-sm">
                        <button type="button" @click="setAddonMode('guidato')"
                                :class="addonMode === 'guidato' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                                class="px-3 py-1">Guidato</button>
                        <button type="button" @click="setAddonMode('diretto')"
                                :class="addonMode === 'diretto' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                                class="px-3 py-1 border-l border-slate-300">Diretto</button>
                    </div>
                </div>

                {{-- Guidato: EAN-5 (prezzo libro) --}}
                <template x-if="addonMode === 'guidato' && type === 'EAN13+5'">
                    <div class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Valuta</label>
                                <select x-model="addonCurrency" @change="composeAddon()"
                                        class="w-full border rounded-lg px-2 py-2 text-sm">
                                    <option value="5">5 — USD $ (Dollaro USA)</option>
                                    <option value="6">6 — CAD $ (Dollaro canadese)</option>
                                    <option value="1">1 — GBP £ (Sterlina)</option>
                                    <option value="0">0 — GBP £ (Sterlina)</option>
                                    <option value="3">3 — AUD $ (Dollaro australiano)</option>
                                    <option value="4">4 — NZD $ (Dollaro neozelandese)</option>
                                    <option value="9">9 — Nessun prezzo (90000)</option>
                                </select>
                            </div>
                            <div x-show="addonCurrency !== '9'">
                                <label class="block text-xs text-slate-500 mb-1">Prezzo</label>
                                <input type="number" step="0.01" min="0" max="99.99"
                                       x-model="addonPrice" @input="composeAddon()"
                                       placeholder="24.95"
                                       class="w-full border rounded-lg px-2 py-2 text-sm font-mono">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">
                            1ª cifra = valuta, restanti 4 = prezzo ×100 (max 99,99).
                            Es. USD 24,95 → <span class="font-mono">52495</span>.
                        </p>
                    </div>
                </template>

                {{-- Guidato: EAN-2 (numero edizione) --}}
                <template x-if="addonMode === 'guidato' && type === 'EAN13+2'">
                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Numero edizione / fascicolo</label>
                            <input type="number" min="0" max="99"
                                   x-model="addonIssue" @input="composeAddon()"
                                   placeholder="7"
                                   class="w-full border rounded-lg px-2 py-2 text-sm font-mono">
                        </div>
                        <p class="text-xs text-slate-500">
                            Supplemento a 2 cifre (00–99), usato sui periodici per il numero progressivo.
                        </p>
                    </div>
                </template>

                {{-- Valore add-on effettivo (inviato col form) --}}
                <div>
                    <label class="block text-xs text-slate-500 mb-1"
                           x-text="addonMode === 'guidato' ? 'Codice add-on generato' : 'Codice add-on'"></label>
                    <input type="text" name="addon" x-model="addon" @input="queuePreview"
                           :readonly="addonMode === 'guidato'"
                           :placeholder="type === 'EAN13+2' ? '07' : '52495'"
                           :class="addonMode === 'guidato' ? 'bg-slate-100 text-slate-500' : ''"
                           class="w-full border rounded-lg px-3 py-2 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Larghezza barre</label>
                <select name="width" x-model="width" @change="queuePreview"
                        class="w-full border rounded-lg px-3 py-2">
                    <option value="fine">Fine</option>
                    <option value="medio">Medio</option>
                    <option value="largo">Largo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Altezza barre: <span x-text="height"></span>px</label>
                <input type="range" name="height" min="30" max="160" x-model="height" @input="queuePreview" class="w-full">
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="show_text" value="1" x-model="showText" @change="queuePreview">
                    <span class="text-sm">Mostra cifre sotto</span>
                </label>
                <label class="flex items-center gap-2">
                    <span class="text-sm">Colore</span>
                    <input type="color" name="color" x-model="color" @input="queuePreview">
                </label>
            </div>

            <hr>

            <div>
                <label class="block text-sm font-medium mb-2">Formati di output</label>
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
                <label class="block text-sm font-medium mb-1">DPI (JPEG)</label>
                <select name="dpi" x-model="dpi" class="w-full border rounded-lg px-3 py-2">
                    <option value="150">150</option>
                    <option value="300">300</option>
                    <option value="600">600</option>
                </select>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-2.5">
                Scarica (<span x-text="formats.length"></span> formati)
            </button>
        </form>

        {{-- ------------------------------------------------ Preview --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="font-semibold mb-3">Anteprima</h2>
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
                <h2 class="font-semibold">Generazione batch (CSV)</h2>
                <p class="text-sm text-slate-500">Colonne: <code>type,code,addon,output_format</code> &middot; scarica uno ZIP.</p>
                <input type="file" name="csv" accept=".csv,.txt" required
                       class="block w-full text-sm border rounded-lg p-2">
                <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg px-4 py-2">
                    Carica ed elabora
                </button>
            </form>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
<script>
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
                    this.error = 'Add-on: inserisci ' + need + ' cifre.';
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
                    this.error = 'Troppe richieste, attendo un istante…';
                    clearTimeout(this._previewTimer);
                    this._previewTimer = setTimeout(() => this.preview(), 1500);
                    return;
                }
                const data = await res.json();
                if (data.ok) { this.svg = data.svg; this.error = ''; }
                else { this.error = data.error || 'Errore di validazione'; }
            } catch (e) {
                this.error = 'Errore di rete';
            }
        },
        onDownload(e) {
            if (this.formats.length === 0) {
                e.preventDefault();
                this.error = 'Seleziona almeno un formato di output.';
            }
        },
    };
}
</script>
</body>
</html>
