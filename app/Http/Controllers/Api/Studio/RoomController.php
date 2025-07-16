<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio\RoomUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function addPresenter(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            
            $roomUser = RoomUser::where('room_id', $request->room_id)
                ->where('user_id', $request->user_id)
                ->first();

            if ($roomUser) {
                return response()->json(['error' => 'User is already a presenter'], 400);
            }

            // Add user as presenter
            RoomUser::create([
                'room_id' => $request->room_id,
                'user_id' => $request->user_id,
                'is_presenter' => true,
            ]);

            return response()->json(['message' => 'User added as presenter'], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to add presenter'], 500);
        }
          
    }
}
