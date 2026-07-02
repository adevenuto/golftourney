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
        // The established index is an optional seed: while a golfer has too few
        // rounds to compute an index (handicap_index stays null below the
        // 3-round minimum) it's how you can gauge their play, but it's never
        // required. Once computed, the field is locked and any value is ignored.
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'manual_handicap_index' => ['nullable', 'numeric', 'between:-9.9,54.0'],
        ];
    }
}
