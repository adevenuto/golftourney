<?php

namespace App\Http\Requests;

use App\Models\User;
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
        // Until a golfer has enough rounds to compute an index (handicap_index
        // stays null below the 3-round minimum), an established index is the
        // only way to gauge their play, so it's required. Once computed, the
        // field is locked and any submitted value is ignored.
        $user = $this->route('user');
        $needsSeed = $user instanceof User && $user->handicap_index === null;

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'manual_handicap_index' => [$needsSeed ? 'required' : 'nullable', 'numeric', 'between:-9.9,54.0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'manual_handicap_index.required' => 'An established index is required until this golfer has 3 rounds logged.',
        ];
    }
}
