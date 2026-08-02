<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Composite uniqueness, ignoring the target currently being edited.
            'measurement_id' => [
                'required',
                'exists:measurements,id',
                Rule::unique('targets', 'measurement_id')
                    ->ignore($this->route('target'))
                    ->where(fn ($q) => $q->where('year', $this->year)->where('quarter', $this->quarter)),
            ],
            'year' => 'required|integer|min:2020|max:2050',
            'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',
            'target' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'measurement_id.exists' => 'Selected measurement does not exist.',
            'measurement_id.unique' => 'A target already exists for this measurement, year, and quarter.',
            'quarter.in' => 'Quarter must be Q1, Q2, Q3, or Q4.',
        ];
    }
}
