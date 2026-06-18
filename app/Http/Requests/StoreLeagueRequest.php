<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeagueRequest extends FormRequest
{
    /**
     * Any authenticated user may create a league (they become its admin).
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
            'name' => 'required|string|max:255',
            'course_id' => 'nullable|integer|exists:courses,id',
            'teebox' => 'nullable|string|max:255',
            'course_rating' => 'required|numeric|min:0|max:100',
            'slope_rating' => 'required|integer|min:55|max:155',
            'recent_rounds' => 'required|integer|min:1|max:100',
            'counting_rounds' => 'required|integer|min:1|lte:recent_rounds',
        ];
    }
}
