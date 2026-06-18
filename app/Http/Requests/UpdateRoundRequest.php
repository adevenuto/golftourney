<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoundRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `admin` route middleware.
     */
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
            'id' => 'required|integer|exists:rounds,id',
            'score' => 'required|integer|min:1|max:150',
            'created_at' => 'required|date',
        ];
    }
}
