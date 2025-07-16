<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if (auth()->check()) {
                auth()->logout();
            }
            return redirect(url("/login") . "?verified=1");
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if (auth()->check()) {
            auth()->logout();
        }
        return redirect(url("/login") . "?verified=1");
    }

    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if(!$user) {
            throw new AuthorizationException();
        }

        if($user->hasVerifiedEmail()) {
            if (auth()->check()) {
                auth()->logout();
            }
            return redirect(url("/login") . "?verified=1");
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException();
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (auth()->check()) {
            auth()->logout();
        }

        return redirect(url("/login") . "?verified=1");
    }
}
