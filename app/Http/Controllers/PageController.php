<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Studio\Room;
use App\Models\Studio\RoomUser;

class PageController extends Controller
{

    public function welcome()
    {
        if(config('setting.onboarding.enabled')) {
            return view('frontend.onboarding.index');
        }
        return view('welcome');
    }

    public function index()
    {
        return view('home');
    }

    public function room($id)
    {
        $room = Room::where('name', $id)->first();
        if (empty($room)) {
            return redirect('/home');
        }
        $user = Auth::user();
        $user->is_admin = ($room->owner_id == $user->id);
        $roomUser = RoomUser::where('room_id', $room->id)->where('user_id', $user->id)->first();
        if (!empty($roomUser)) {
            $user->is_allowed = true;
        }
        $roomId = $room->id;
        return view('room', compact('room'));
    }

    public function studio()
    {
        return view('apps.studio');
    }

    public function video()
    {
        // $room = \App\Models\Room::where('id', $id)->first();
        // if (empty($room)) {
        //     return redirect('/home');
        // }
        // $user = Auth::user();
        // $user->is_admin = ($room->owner_id == $user->id);
        // $roomUser = RoomUser::where('room_id', $room->id)->where('user_id', $user->id)->first();
        // if (!empty($roomUser)) {
        //     $user->is_allowed = true;
        // }
        // $roomId = $room->id;
        return view('video');
    }

    public function changeLanguage($lang)
    {
        app()->setLocale($lang);
        cookie()->queue('locale', $lang, 60 * 24 * 30);
        return redirect()->back();
    }
}
