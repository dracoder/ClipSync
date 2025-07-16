@extends('layouts.master')

@section('title', __('Guidelines'))
@section('descrizioneSeo', __('Learn how to use the platform by respecting a few simple rules. We want the best experience for everyone, without exceptions').".")

@section('content')
    <section class="color-bg animate__animated animate__slideInDown" style="border-radius: 0 0 90px 0;">
        <div class="container py-5">
            <div class="col-12 text-center my-5 pb-3">
                <h1 class="hero-text text-xl md:text-4xl xl:text-8xl animate__animated animate__fadeIn clip-text drop-shadow-lg">
                    {{__('Guidelines')}}
                </h1>
            </div>
        </div>
    </section>

    <section class="py-4 animate__animated animate__fadeIn animate__delay-1s">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p>
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Consectetur consequatur dignissimos esse est ex exercitationem explicabo, facere id labore officia pariatur repudiandae sint, tempore temporibus veritatis vero voluptates voluptatibus? Sunt.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
