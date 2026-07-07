<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRequest extends FormRequest
{
    /**
     * Any authenticated user may start a game (auth is enforced by the route).
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
            'course_id' => 'required|integer|exists:courses,id',
            'teebox' => 'nullable|string|max:255',
        ];
    }
}
