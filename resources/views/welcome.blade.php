@extends('layouts.master')

@section('title', __('Video Chat Gratis and More'))
@section('descrizioneSeo', '')

@section('content')

    <section class="color-bg  animate__animated animate__slideInDown" style="border-radius: 0 0 90px 0;">
        <div class="container mx-auto py-5">
            <div class="col-12 text-center my-5 pb-3">
                <h1 class="text-xl md:text-4xl xl:text-8xl animate__animated animate__fadeIn clip-text drop-shadow-lg">
                    {{ __('home.hero_title1') }}, <br class="hidden sm:block">
                    {{ __('home.hero_title2') }}
                </h1>
                <h3 class="hero-text">{{__('home.hero_description')}}</h3>
            </div>
        </div>
    </section>

    {{--
    <section class="animate__animated animate__fadeIn animate__delay-1s" style="margin: 5vh auto;">
        @livewire('frontend.onboarding')
    </section>
    --}}

    @include('frontend.features')


    <section class="py-5 animate__animated animate__fadeIn animate__delay-1s" style="background-color: #fefefe;border-radius: 0 90px 90px 0;">
        <div class="container mx-auto">
            <div class="row d-flex justify-content-center align-items-center">
                
                {{-- <p class="lead mt-4 text-center">
                    ❤️ {{__('We constantly improve the project by adding new features, also thanks to the suggestions of our users')}}. ❤️
                    <br>
                </p> --}}

                <br><br>
                

            </div>
        </div>
    </section>

@endsection
