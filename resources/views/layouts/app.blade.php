<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema Financeiro') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div class="min-h-screen flex flex-col bg-gray-50">
            @include('layouts.navigation')

            <!-- Cabeçalho da página -->
            @isset($header)
                <header class="bg-white border-b border-gray-200 print:hidden">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Conteúdo -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="py-4 text-center text-xs text-gray-400 print:hidden">
                Sistema Financeiro &middot; {{ now()->format('Y') }}
            </footer>
        </div>
    </body>
</html>
