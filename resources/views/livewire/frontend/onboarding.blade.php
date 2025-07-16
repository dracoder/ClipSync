<div>
    <div class="bg-yellow-200 p-10 shadow-brutal border-2 border-black rounded-xl h-full max-w-max">
        @if($verified)
            <h1 class="text-center animate__animated animate__tada">
               {!! __('messages.onboarding.verified.title') !!}
            </h1>
            <p class="text-center">
                {!! __('messages.onboarding.verified.message') !!}
            </p>
        @elseif ($onboarding)
            <div class="flex w-full flex-col gap-2">
                <p class="text-center">
                    {!! __('onboarding.description') !!}
                </p>
                <div class="mt-5">
                    <input type="email" class="input-theme shadow-brutal w-full px-2 py-1 rounded-xl"
                        placeholder="{{ __('enter_your_email') }}" wire:model="email" wire:keydown.enter="onboard">
                    @error('email')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="text-center">
                    <button class="btn btn-warning btn-theme rounded-md mt-2"  
                        wire:loading.class="opacity-50"
                        wire:click="onboard" wire:loading.attr="disabled">
                        {{ __('onboarding.button') }}
                    </button>
                </div>
                <p class="text-xs text-center mt-2">
                    {!! __('onboarding.privacy.prefix') !!}
                    <a href="{{ url('/privacy') }}" class="black-link" target="_blank">{!! __('onboarding.privacy.link') !!} </a>
                    {!! __('onboarding.privacy.suffix') !!}
                    <br>
                    <i>{!! __('onboarding.privacy.closing') !!}</i>
                </p>
            </div>
        @else
            <h1 class="text-center animate__animated animate__tada">
                👍 OK! {{ __("You're almost done") }}.
            </h1>
            <p class="text-center">
                {{ __('To validate your request we just sent an email to') }} <b>{{ $email }}</b>.<br>
                {{ __('Please also check in SPAM folder') }}.
            </p>
        @endif

    </div>
</div>
