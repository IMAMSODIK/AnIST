<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <img src="{{ asset('logo/anist.png') }}" alt="AnIST" class="inline-block w-16 h-16 rounded-2xl object-contain mb-4">
                <h1 class="text-2xl font-bold text-slate-800">{{ config('app.name') }}</h1>
                <p class="text-slate-500 mt-1">An Intelligent Strategic Tool</p>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
