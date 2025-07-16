@extends('layouts.guest')

@section('title', __('register'))

@section('content')
    <div class="w-full max-w-[500px] bg-yellow-300 mx-auto p-5 shadow-brutal border-2 border-black rounded-lg">
        <div class="grid grid-cols-1 gap-5">
            <div>
                <h1 class="text-2xl font-bold ">{!! __('messages.verify_email.title') !!}</h1>
            </div>
            <div>
                @if (session('resent'))
                    <div class="px-3 py-1 mb-2 bg-success text-white rounded-md" role="alert">
                        {!! __('messages.verify_email.resend_success') !!}
                    </div>
                @endif

                {!! __('messages.verify_email.message',['email' => auth()->user()->email]) !!} <br>
                {!! __('messages.verify_email.not_received') !!}, <br>
                <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit"
                        class="text-primary  hover:text-primary-dark focus:outline-none focus:underline transition duration-150 ease-in-out text-center">
                        {!! __('messages.verify_email.request_another') !!}
                </form>
            </div>
        </div>
    </div>
@endsection