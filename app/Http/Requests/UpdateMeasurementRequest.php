<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeasurementRequest extends FormRequest
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
            'weight' => 'nullable|numeric|min:0|max:100',
            // Targets entered inline on the same form.
            // Year is required only when at least one quarter value is filled.
            'target_year' => [
                'nullable',
                'integer',
                'min:2020',
                'max:2050',
                function ($attribute, $value, $fail) {
                    $hasTarget = collect(['q1', 'q2', 'q3', 'q4'])
                        ->contains(fn ($q) => filled($this->input("targets.{$q}")));
                    if ($hasTarget && blank($value)) {
                        $fail('The target year is required when a quarterly target is provided.');
                    }
                },
            ],
            'targets' => 'nullable|array',
            'targets.q1' => 'nullable|numeric|min:0',
            'targets.q2' => 'nullable|numeric|min:0',
            'targets.q3' => 'nullable|numeric|min:0',
            'targets.q4' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'perspective.in' => 'Perspective must be one of: Financial, Customer, Internal Process, Learning & Growth',
            'formula.in' => 'Formula must be one of: Higher is Better, Lower is Better, Exact Target',
            'weight.max' => 'Weight cannot exceed 100%',
            'targets.q1.numeric' => 'Q1 target must be a number.',
            'targets.q2.numeric' => 'Q2 target must be a number.',
            'targets.q3.numeric' => 'Q3 target must be a number.',
            'targets.q4.numeric' => 'Q4 target must be a number.',
        ];
    }

    /**
     * weight is intentionally hidden from the form to avoid confusing users
     * during measurement entry. It defaults to 100 (equal weighting) so the
     * weighted-average KPI calculation still works correctly.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            if (!isset($data['weight'])) {
                $data['weight'] = 100;
            }
            // Normalize empty target strings to null so the controller can
            // tell which quarters were intentionally left blank.
            if (isset($data['targets'])) {
                foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                    if (array_key_exists($q, $data['targets']) && blank($data['targets'][$q])) {
                        $data['targets'][$q] = null;
                    }
                }
            }
        }

        return $data;
    }
}
