@extends('layouts.app')

@php
    $name    = config('legal.owner_name') ?: '—';
    $email   = config('legal.contact_email') ?: '—';
    $hosting = config('legal.hosting') ?: '—';
    $date    = config('legal.updated_at') ?: __('legal.no_date');
@endphp

@section('content')
<article class="bg-white rounded-xl shadow p-6 md:p-8 max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('legal.privacy.title') }}</h1>
    <p class="text-xs text-slate-400 mt-1">{{ __('legal.updated', ['date' => $date]) }}</p>
    <p class="text-slate-600 leading-relaxed mt-4">{{ __('legal.privacy.intro') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.controller_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.controller', ['name' => $name, 'email' => $email]) }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.data_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.data') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.purpose_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.purpose') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.retention_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.retention') }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.recipients_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.recipients', ['hosting' => $hosting]) }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.rights_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.rights', ['email' => $email]) }}</p>

    <h2 class="text-lg font-semibold text-slate-900 mt-6">{{ __('legal.privacy.profiling_t') }}</h2>
    <p class="text-slate-600 leading-relaxed mt-2">{{ __('legal.privacy.profiling') }}</p>

    <p class="mt-8"><a href="{{ route('barcode.index') }}" class="text-indigo-600 hover:underline">&larr; {{ __('legal.home') }}</a></p>
</article>
@endsection
