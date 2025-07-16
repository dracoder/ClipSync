@component('mail::message')
<h1>{{ __('messages.onboarding.verification_mail.greeting')}}</h1>

<p>{{ __('messages.onboarding.verification_mail.message')}}</p>
@component('mail::button', ['url' => route('onboarding.verify',['token' => $onboardingUser->verification_token])])
{!! __('messages.onboarding.verification_mail.button') !!}
@endcomponent

<p>
{{ __('messages.onboarding.verification_mail.salutation')}}<br>
{{ config('app.name') }}
</p>
@endcomponent