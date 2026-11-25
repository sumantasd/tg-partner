<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') — Tech Googly Partner Program</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased text-tg-dark bg-[#fafafa]">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <!-- Official Tech Googly Logo -->
            <a href="{{ route('home') }}" class="inline-block transition hover:opacity-95">
                <img src="{{ asset('images/brand/tech-googly-logo.png') }}"
                     alt="Tech Googly Logo"
                     class="h-24 w-auto mx-auto object-contain drop-shadow-sm">
            </a>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-tg-dark">
                Tech Googly
            </h2>
            <p class="text-xs uppercase tracking-wider font-bold text-tg-pink mt-0.5">
                Innovation For You
            </p>
            <p class="mt-2 text-sm text-slate-500 font-medium">
                @yield('subtitle', 'Partner Program Portal')
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full @yield('max-width', 'sm:max-w-md')">
            <div class="bg-white py-8 px-6 shadow-sm border border-slate-200/80 sm:rounded-2xl sm:px-10">
                @if (session('success'))
                    <div class="mb-5 rounded-xl bg-tg-green-light p-4 border border-tg-green-border text-sm font-medium text-tg-green-hover flex items-center">
                        <svg class="w-5 h-5 text-tg-green mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-5 rounded-xl bg-amber-50 p-4 border border-amber-200 text-sm font-medium text-amber-800 flex items-center">
                        <svg class="w-5 h-5 text-amber-600 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 rounded-xl bg-tg-blue-light p-4 border border-tg-blue-border text-sm font-medium text-tg-blue-hover">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-tg-pink-light p-4 border border-tg-pink-border text-sm text-tg-pink-hover">
                        <div class="font-semibold mb-1">Please fix the following:</div>
                        <ul class="list-disc list-inside text-xs space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>

            <div class="mt-8 text-center text-xs text-slate-500 space-y-1">
                <div>&copy; {{ date('Y') }} Tech Googly. All rights reserved. &bull; <span class="font-semibold text-tg-pink">Innovation For You</span></div>
                <div><a href="https://techgoogly.com" target="_blank" class="hover:underline text-tg-purple font-medium">techgoogly.com</a></div>
            </div>
        </div>
    </div>
</body>
</html>
