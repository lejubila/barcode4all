@extends('layouts.app')

@php $date = config('legal.updated_at') ?: __('legal.no_date'); @endphp

@section('content')
<article class="bg-white rounded-xl shadow p-6 md:p-8 max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('legal.terms.title') }}</h1>
    <p class="text-xs text-slate-400 mt-1">{{ __('legal.updated', ['date' => $date]) }}</p>
    <p class="text-slate-600 leading-relaxed mt-4">{{ __('legal.terms.intro') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.terms.service_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.terms.service') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.terms.nowarranty_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.terms.nowarranty') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.terms.use_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.terms.use') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.terms.law_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.terms.law') }}</p>

    <p class="mt-8"><a href="{{ route('barcode.index') }}" class="text-indigo-600 hover:underline">&larr; {{ __('legal.home') }}</a></p>
</article>
@endsection
