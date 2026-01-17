<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @stack('styles')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance

</head>

<body class="min-h-screen lg:bg-blue-100/50 dark:bg-zinc-800">

    <!-- Global Export Progress Bar - Fixed at top, visible on all pages -->
    <x-export-progress-bar-top></x-export-progress-bar-top>

    <x-layouts.app.secondary-header />
    <flux:main wrapper="false" class="p-2! flux-no-padding flux-no-margin overflow-x-hidden">
        {{ $slot }}
    </flux:main>
    {{-- Flux Scripts --}}
    @fluxScripts
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
</body>

</html>