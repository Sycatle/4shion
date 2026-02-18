<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-concrete font-sans">
        <main class="shell relative flex min-h-screen flex-col justify-between py-8">
            <a href="{{ url('/') }}" aria-label="4shion, accueil"><x-shop.emblem class="h-8 w-auto" /></a>
            <div class="max-w-xl pb-16">
                <p class="display text-[clamp(6rem,24vw,16rem)]">{{ $code }}</p>
                <h1 class="mt-6 text-4xl">{{ $heading }}</h1>
                <p class="mt-3 text-base">{{ $message }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/') }}" class="btn-primary">Retour à l'accueil</a>
                    {{ $actions ?? '' }}
                </div>
            </div>
        </main>
    </body>
</html>
