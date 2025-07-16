<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>@yield('title') - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, user-scalable=no" />
    <meta charset="UTF-8">
    <meta name="description" content="@yield('descrizioneSeo')">
    <meta name="keywords" content="@yield('keywordsSeo')">
    <meta name="author" content="ClipSync.com">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ Request::fullUrl() }}">

    <link rel="icon" type="image/svg" href="{{ url('img/clipsync.svg') }}">
    <meta charset="utf-8">

    {{-- necessarie entrambe le successive righe per la protezione csrf --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        }
    </script>

    @vite(['resources/sass/app.scss'])
    @stack('css')

    @livewireStyles
</head>

<body>
    <section class="full-height">
        <div class="main-wrapper w-full h-screen color-bg">
            <div class="w-full h-screen overflow-auto" id="content-container">
                <div class="absolute top-0 right-0 p-4">
                    <x-language-switcher />
                </div>
                <div class="flex flex-col gap-4 w-full h-full justify-center items-center">
                    <div class="flex items-center">
                        <img src="{{ url('img/clipsync.svg') }}" alt="ClipSync Logo" class="h-20 w-20" />
                        <a href="/" class="no-link-color -ml-2 -mt-2">
                            <p class="clip-logo">CLIPSYNC</p>
                        </a>
                    </div>
                    <div class="flex flex-col w-full">
                        @yield('content')
                        {{ $slot ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @vite(['resources/js/app.js'])
    @stack('js')

    @livewireScripts

</body>

</html>
