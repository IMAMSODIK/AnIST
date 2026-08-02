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
        if (is_array($data) && !isset($data['weight'])) {
            $data['weight'] = 100;
        }
        return $data;
    }
}
