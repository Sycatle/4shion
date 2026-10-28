@props(['title' => null, 'description' => null, 'canonical' => null, 'image' => null, 'ogType' => 'website', 'noindex' => null])

@php
    $fullTitle = ($title ? $title.' — ' : '').config('app.name');
    $canonical ??= url()->current();
    $noindex ??= request()->routeIs('cart', 'checkout*', 'account.*', 'profile', 'login', 'register', 'password.*', 'verification.*');
    $image ??= asset('images/hero.webp');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $fullTitle }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        <link rel="canonical" href="{{ $canonical }}">
        @if ($noindex)
            <meta name="robots" content="noindex, follow">
        @endif

        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ $image }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        <meta name="twitter:image" content="{{ $image }}">

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @stack('structured-data')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col font-sans">
        <x-shop.header />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <x-shop.footer />

        <livewire:shop.cart-drawer />
        <livewire:shop.search-overlay />
        <x-shop.cookie-banner />
    </body>
</html>
