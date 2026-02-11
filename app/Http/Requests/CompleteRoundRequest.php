<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRoundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scores'                             => 'required|array',
            'scores.*.player_id'                 => 'required|integer|exists:round_players,id',
            'scores.*.hole_scores'               => 'required|array',
            'scores.*.hole_scores.*.hole_number' => 'required|integer|min:1',
            'scores.*.hole_scores.*.score'       => 'required|integer|min:1|max:20',
            'scores.*.hole_scores.*.memo'        => 'nullable|string|max:255',
        ];
    }
}
