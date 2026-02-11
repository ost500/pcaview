<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoundRequest extends FormRequest
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
            'course_id'         => 'nullable|exists:park_golf_courses,id',
            'course_name'       => 'required|string|max:255',
            'hole_count'        => 'required|integer|in:9,18',
            'hole_pars'         => 'required|array',
            'hole_pars.*'       => 'required|integer|min:3|max:5',
            'played_at'         => 'required|date|before_or_equal:today',
            'memo'              => 'nullable|string|max:1000',
            'players'           => 'required|array|min:1|max:6',
            'players.*.name'    => 'required|string|max:100',
            'players.*.is_me'   => 'required|boolean',
            'players.*.user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate hole_pars count matches hole_count
            if ($this->has('hole_pars') && $this->has('hole_count')) {
                if (count($this->hole_pars) !== $this->hole_count) {
                    $validator->errors()->add('hole_pars', 'The number of pars must match the hole count.');
                }
            }

            // Validate exactly one player has is_me = true
            if ($this->has('players')) {
                $isMeCount = collect($this->players)->filter(fn ($p) => $p['is_me'] ?? false)->count();
                if ($isMeCount !== 1) {
                    $validator->errors()->add('players', 'Exactly one player must be marked as "is_me".');
                }
            }
        });
    }
}
