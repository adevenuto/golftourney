<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'join_code' => 'required|string|max:8',
        ];
    }
}
