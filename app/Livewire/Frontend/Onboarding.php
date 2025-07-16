<?php

namespace App\Livewire\Frontend;

use App\Mail\Onboarding\VerificationMail;
use App\Models\OnboardingUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

class Onboarding extends Component
{
    public $onboarding = true, $verified = false;
    public $email = null;

    public function mount()
    {
        if(Route::currentRouteName() == 'onboarding.verify') {
            $onboardingUser = OnboardingUser::where('verification_token', request()->token)->first();
            if($onboardingUser) {
                $onboardingUser->update(['verified' => 1]);
                $this->verified = true;
            }
        }
    }

    public function render()
    {
        return view('livewire.frontend.onboarding');
    }

    public function onboard()
    {
        $this->validate([
            'email' => 'required|email|unique:onboarding_users|indisposable'
        ],[
            'email.unique' =>  __('messages.onboarding.email_unique')
        ]);

        try {
            DB::beginTransaction();
            $user = OnboardingUser::create([
                'email' => $this->email,
                'verified' => 0,
                'verification_token' => Str::random(40) . Str::random(),
            ]);

            Mail::to($this->email)->send(new VerificationMail($user));
            DB::commit();

        $this->onboarding = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('email', $e->getMessage());
        }
    }
}
