<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name') }} | @yield('title') </title>
    <meta name="viewport" content="width=device-width, user-scalable=no" />
    <meta charset="UTF-8">
    <meta name="description" content="@yield('descrizioneSeo')">
    <meta name="keywords" content=" ">
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
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('css')
</head>

<body>

    <section class="full-height">
        @yield('content')
        {{ $slot ?? '' }}
    </section>

</body>

</html>
