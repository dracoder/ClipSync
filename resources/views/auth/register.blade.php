@extends('layouts.guest')

@section('title', __('register'))

@section('content')
    <div class="w-full max-w-[500px] bg-yellow-300 mx-auto p-5 shadow-brutal border-2 border-black rounded-lg">
        <div class="grid grid-cols-1 gap-5">
            <div>
                <h1 class="text-2xl font-bold ">{{ __('register') }}</h1>
            </div>
            <div>
                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label for="name" class="font-semibold">{{ __('name') }}</label>
                            <input type="name" id="name" name="name" class="w-full p-2 border-2 border-black rounded-lg focus-visible:outline-none" value="{{ old('name') }}" />
                            @error('name')
                                <p class="text-red-500">{{ $message }}</p>
                            @enderror

                        </div>
                        <div>
                            <label for="email" class="font-semibold">{{ __('email') }}</label>
                            <input type="email" id="email" name="email" class="w-full p-2 border-2 border-black rounded-lg focus-visible:outline-none" value="{{ old('email') }}" />
                            @error('email')
                                <p class="text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="font-semibold">{{ __('password') }}</label>
                            <div class="relative">
                                <input type="password" x-ref="passwordInput" id="password" name="password"
                                    class="w-full p-2 pr-5 border-2 border-black rounded-lg focus-visible:outline-none" />
                                <label class="absolute right-3 top-[0.75rem] cursor-pointer swap  text-2xl">
                                    <i class="iconoir-eye swap-off"></i>
                                    <i class="iconoir-eye-closed swap-on"></i>
                                </label>
                            </div>
                            @error('password')
                                <p class="text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password_confirmation"
                                class="font-semibold">{{ __('password_confirmation') }}</label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="w-full p-2 pr-5 border-2 border-black rounded-lg focus-visible:outline-none" />
                            </div>
                            @error('password_confirmation')
                                <p class="text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <button type="submit"
                                class="w-full p-2 bg-orange-400 border-2 border-orange-800 text-black hover:bg-orange-600 transition-all duration-300 rounded-lg uppercase font-black">
                                {{ __('register') }}
                            </button>
                            <p class="text-center mt-4">
                                {{ __('already_registered') }} <a href="{{ url('/login') }}" class=" hover:underline">{{ __('login') }}</a>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        const passwordInput = document.querySelector('#password');
        const passwordConfirmationInput = document.querySelector('#password_confirmation');
        const swap = document.querySelector('.swap');
        const swapOff = document.querySelector('.swap-off');
        const swapOn = document.querySelector('.swap-on');

        swap.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordConfirmationInput.type = 'text';
                swap.classList.add('swap-active');
            } else {
                passwordInput.type = 'password';
                passwordConfirmationInput.type = 'password';
                swap.classList.remove('swap-active');
            }
        });

    </script>
@endpush