<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Panel' }} · {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant:300,400,500,400i|jost:300,400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="min-h-screen bg-paper">
            @include('layouts.navigation')

            <div class="mx-auto max-w-6xl px-6 py-10 sm:px-8">
                <header class="mb-10 border-b border-line pb-6">
                    <span class="a-eyebrow">{{ $eyebrow ?? 'Panel' }}</span>
                    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                        <h1 class="a-title">{{ $heading ?? ($title ?? '') }}</h1>
                        @isset($actions)
                            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                </header>

                @if (session('status'))
                    <div class="mb-8 border border-line bg-panel px-4 py-3 text-[11px] uppercase tracking-label text-ink">
                        {{ session('status') }}
                    </div>
                @endif

                <main>{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
