<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function response($success, $data, $message, $code = 200)
    {
        $response = array(
            'success' => $success,
            'data' => $data,
            'message' => $message
        );
        return response()->json($response, $code);
    }
}
