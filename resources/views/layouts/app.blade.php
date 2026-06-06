<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <script src="{{ asset('vendor/tailwind/tailwind.js') }}"></script>
    <script defer src="{{ asset('vendor/alpine/alpine.min.js') }}"></script>
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col">
    {{-- Barra superiore: nome app (link home) + selettore lingua --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto w-full px-6 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('barcode.index') }}" class="font-semibold text-slate-900">{{ config('app.name') }}</a>
            <nav class="flex items-center gap-2 text-sm shrink-0">
                @foreach (['it' => 'IT', 'en' => 'EN'] as $loc => $label)
                    <a href="{{ route('locale.switch', $loc) }}"
                       class="px-2 py-1 rounded {{ app()->getLocale() === $loc ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-500 hover:bg-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    <main class="max-w-5xl mx-auto w-full p-6 flex-1">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 mt-6">
        <div class="max-w-5xl mx-auto w-full px-6 py-6 text-sm text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <a href="{{ route('legal.privacy') }}" class="hover:text-slate-800">{{ __('messages.nav_privacy') }}</a>
                <a href="{{ route('legal.cookie') }}" class="hover:text-slate-800">{{ __('messages.nav_cookie') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-slate-800">{{ __('messages.nav_terms') }}</a>
                <a href="https://github.com/lejubila/barcode4all" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 hover:text-slate-800">
                    <svg viewBox="0 0 16 16" class="w-4 h-4 fill-current" aria-hidden="true">
                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0016 8c0-4.42-3.58-8-8-8z"/>
                    </svg>
                    GitHub
                </a>
            </div>
            <div class="text-center sm:text-right">
                <div>&copy; {{ date('Y') }} @if(config('legal.owner_name')) {{ config('legal.owner_name') }} @else {{ config('app.name') }} @endif</div>
                <div class="text-xs text-slate-400">{{ __('messages.footer_tagline') }}</div>
            </div>
        </div>
    </footer>

    {{-- Avviso informativo (solo cookie tecnici): NON è un banner di consenso. --}}
    <div x-data="{ show: false }" x-init="show = !localStorage.getItem('cookieNoticeDismissed')"
         x-show="show" x-cloak
         class="fixed bottom-0 inset-x-0 bg-slate-900 text-white text-sm shadow-lg">
        <div class="max-w-5xl mx-auto w-full px-6 py-3 flex items-center justify-between gap-4">
            <p>{{ __('messages.cookie_notice') }}
                <a href="{{ route('legal.cookie') }}" class="underline">{{ __('messages.nav_cookie') }}</a>
            </p>
            <button type="button"
                    @click="localStorage.setItem('cookieNoticeDismissed','1'); show = false"
                    class="shrink-0 bg-white text-slate-900 font-semibold rounded px-3 py-1">
                {{ __('messages.cookie_notice_ok') }}
            </button>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
