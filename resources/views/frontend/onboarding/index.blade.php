@extends('layouts.full')

@section('title', 'Coming Soon')

@push('css')
<style>
    @import url("https://fonts.bunny.net/css?family=Merriweather+Sans|Bungee");
    main {
        height: 100vh;
        width: 100vw;
        background: #ffff00;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-family: "Merriweather Sans", sans-serif;
    }
    main #errorText {
        font-size: 22px;
        margin: 14px 0;
    }
    main #errorLink {
        font-size: 20px;
        padding: 12px;
        border: 1px solid;
        color: #000;
        background-color: transparent;
        text-decoration: none;
        transition: all 0.5s ease-in-out;
    }
    main #errorLink:hover, main #errorLink:active {
        color: #fff;
        background: #000;
    }
    main #g6219 {
        transform-origin: 85px 4px;
        -webkit-animation: an1 12s 0.5s infinite ease-out;
        animation: an1 12s 0.5s infinite ease-out;
    }

    @-webkit-keyframes an1 {
        0% {
            transform: rotate(0);
        }
        5% {
            transform: rotate(3deg);
        }
        15% {
            transform: rotate(-2.5deg);
        }
        25% {
            transform: rotate(2deg);
        }
        35% {
            transform: rotate(-1.5deg);
        }
        45% {
            transform: rotate(1deg);
        }
        55% {
            transform: rotate(-1.5deg);
        }
        65% {
            transform: rotate(2deg);
        }
        75% {
            transform: rotate(-2deg);
        }
        85% {
            transform: rotate(2.5deg);
        }
        95% {
            transform: rotate(-3deg);
        }
        100% {
            transform: rotate(0);
        }
    }

    @keyframes an1 {
        0% {
            transform: rotate(0);
        }
        5% {
            transform: rotate(3deg);
        }
        15% {
            transform: rotate(-2.5deg);
        }
        25% {
            transform: rotate(2deg);
        }
        35% {
            transform: rotate(-1.5deg);
        }
        45% {
            transform: rotate(1deg);
        }
        55% {
            transform: rotate(-1.5deg);
        }
        65% {
            transform: rotate(2deg);
        }
        75% {
            transform: rotate(-2deg);
        }
        85% {
            transform: rotate(2.5deg);
        }
        95% {
            transform: rotate(-3deg);
        }
        100% {
            transform: rotate(0);
        }
    }

    .bungee {
        font-family: "Bungee", sans-serif;
        font-size: 2.5em;
    }
</style>
@endpush

@section('content')
<main>
                    <img src="{{url('/img/clipsync.svg')}}" alt="ClipSync Logo" style="max-width: 200px;">
    <p class="bungee" style="padding: 10px;text-align: center;">SOMETHING VERY GOOD IS COMING SOON!</p>
    <p>Our birds are working hard...</p>
    <div class="flex w-full px-2 flex-col justify-center items-center">
        @livewire('frontend.onboarding')
    </div>
</main>
@endsection