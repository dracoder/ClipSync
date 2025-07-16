<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\Users\ProfileResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $authenticated = auth()->attempt($credentials);
        if (!$authenticated) {
            return $this->response(false, [], __('messages.invalid_credentials'), 401);
        }
        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $data = [
            'token' => $token,
        ];
        return $this->response(true, $data, __('messages.login_successfully'));
    }


    public function getUserDetails(Request $request)
    {
        try {
            $user = $request->user();
            $data = array(
                'user' => new ProfileResource($user),
            );
            return  $this->response(true, $data, __('messages.user_details'));
        } catch (\Throwable $e) {
            log_error($e);
            return  $this->response(false, [], $e->getMessage());
        }
    }


    public function logout(Request $request)
    {
        $user = $request->user();
        // delete current user token
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return $this->response(true, [], __('messages.logout_success'));
    }


    public function updateProfile(ProfileUpdateRequest $request)
    {
        try {
            $user = $request->user();
            $data = $request->all();
            if ($request->has('password')) {
                $data['password'] = bcrypt($request->password);
            }
            $user->update($data);
            $data = array(
                'user' => new ProfileResource($user),
            );
            return  $this->response(true, $data, __('messages.model_action.update_success', ['attribute' => __('messages.profile')]));
        } catch (\Throwable $e) {
            log_error($e);
            return  $this->response(false, [], $e->getMessage());
        }
    }
}
