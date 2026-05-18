<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Todo') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-zinc-900">
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-b from-green-50 via-white to-white"></div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <header class="flex items-center justify-between py-8">
            <div>
                <p class="text-xs font-semibold tracking-wide text-green-700">PUBLIC · SINGLE USER</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Todo List</h1>
                <p class="mt-1 text-sm text-zinc-600">Minimal, card-based, deadline-aware.</p>
            </div>

            <div class="flex items-center gap-2">
                {{ $headerActions ?? '' }}
            </div>
        </header>

        <main class="pb-12">
            {{ $slot }}
        </main>
        
    </div>
</body>
</html>
