<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title') - {{config('app.name')}}</title>
    <meta name="viewport" content="width=device-width, user-scalable=no" />
    <meta charset="UTF-8">
    <meta name="description" content="@yield('descrizioneSeo')">
    <meta name="keywords" content="@yield('keywordsSeo')">
    <meta name="author" content="">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{Request::fullUrl()}}">

    <link rel="icon"
          type="image/svg"
          href="{{url('img/clipsync.svg')}}">
    <meta charset="utf-8">

    {{-- necessarie entrambe le successive righe per la protezione csrf --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.Laravel = { csrfToken : '{{ csrf_token() }}' }</script>

    @vite(['resources/sass/app.scss'])
    @stack('css')
</head>
<body>

<div id="app" class="full-height">  
    @yield('content')
    {{ $slot ?? '' }}
</div>
@vite(['resources/js/app.js'])
@stack('js')
</body>
</html>
