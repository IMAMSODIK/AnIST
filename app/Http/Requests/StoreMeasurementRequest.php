<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'perspective' => 'required|string|in:Financial,Customer,Internal Process,Learning & Growth',
            'objective' => 'required|string|max:255',
            'measurement' => 'required|string|max:255',
            'definition' => 'nullable|string',
            'formula' => 'nullable|string|in:Higher is Better,Lower is Better,Exact Target',
            'unit' => 'nullable|string|max:50',
            'weight' => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'perspective.in' => 'Perspective must be one of: Financial, Customer, Internal Process, Learning & Growth',
            'formula.in' => 'Formula must be one of: Higher is Better, Lower is Better, Exact Target',
            'weight.max' => 'Weight cannot exceed 100%',
        ];
    }
}
