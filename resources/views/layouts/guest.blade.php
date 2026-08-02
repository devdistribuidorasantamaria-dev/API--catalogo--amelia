<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Acceso · {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant:300,400,500,400i|jost:300,400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="flex min-h-screen flex-col items-center justify-center bg-paper px-6 py-12">
            <div class="text-center">
                <p class="font-serif text-4xl tracking-wide text-ink">Amelia</p>
                <div class="mx-auto my-6 h-px w-12 bg-ink opacity-50"></div>
                <p class="a-eyebrow">Panel del catálogo</p>
            </div>

            <div class="mt-10 w-full max-w-md border border-line bg-panel px-8 py-9">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
