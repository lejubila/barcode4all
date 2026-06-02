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
