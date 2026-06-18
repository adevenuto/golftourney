<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGolferRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'handicap' => 'required|numeric|min:0',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }
}
