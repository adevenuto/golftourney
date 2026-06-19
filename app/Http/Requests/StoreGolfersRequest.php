<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreGolfersRequest extends FormRequest
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
            'golfers' => 'required|array|min:1|max:100',
            'golfers.*.golfer_id' => 'nullable|integer|exists:golfers,id',
            'golfers.*.first_name' => 'nullable|string|max:255',
            'golfers.*.last_name' => 'nullable|string|max:255',
            'golfers.*.email' => 'nullable|email|max:255',
            'golfers.*.phone' => 'nullable|string|max:255',
        ];
    }

    /**
     * Each row must be either an existing golfer (golfer_id) or a new one
     * (first + last name).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rows = $this->input('golfers');

            if (! is_array($rows)) {
                return;
            }

            foreach ($rows as $i => $row) {
                $hasId = ! empty($row['golfer_id']);
                $hasName = ! empty($row['first_name']) && ! empty($row['last_name']);

                if (! $hasId && ! $hasName) {
                    $validator->errors()->add(
                        "golfers.{$i}.first_name",
                        'Enter a first and last name, or pick an existing golfer.'
                    );
                }
            }
        });
    }
}
