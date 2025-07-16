<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    public function failedValidation(Validator $validator)
    {
        $response = array(
            'success' => false,
            'errors' => $validator->errors(),
            'message' => $validator->errors()->first()
        );

        throw new HttpResponseException(response()->json($response, 422));
    }
}
