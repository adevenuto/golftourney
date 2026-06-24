<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoundRequest extends FormRequest
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
            'score' => 'required|integer|min:1|max:150',
            'created_at' => 'required|date',
            // Present ⇒ a league round, snapshotting that league's course context.
            'league_id' => 'nullable|integer|exists:leagues,id',
            // Present ⇒ a casual round at this catalog course/teebox (no league).
            'course_id' => 'nullable|integer|exists:courses,id',
            'teebox' => 'nullable|string|max:255',
        ];
    }
}
