<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGameScoreRequest extends FormRequest
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
        $game = $this->route('game');
        $maxHole = $game instanceof Game ? $game->holes : 18;

        return [
            'hole' => "required|integer|min:1|max:{$maxHole}",
            'strokes' => 'nullable|integer|min:1|max:20',
        ];
    }
}
