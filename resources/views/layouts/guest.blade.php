<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sistema Financeiro</title>

        <!-- Fontes -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 flex items-center justify-center min-h-screen">

        <!-- Caixa Central do Login -->
        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl rounded-2xl border border-gray-100">

            <!-- Título do Sistema (Substituindo a Logo) -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">
                    SISTEMA <span class="text-blue-600">FINANCEIRO</span>
                </h1>
                <p class="text-sm text-gray-500 mt-2 font-medium">Acesso restrito e seguro</p>
            </div>

            <!-- Aqui dentro vai o formulário de login -->
            {{ $slot }}

        </div>
    </body>
</html>
