<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measurement_id' => 'required|exists:measurements,id',
            'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',
            'year' => 'required|integer|min:2020|max:2050',
            'file' => 'required|file|max:10240|mimes:pdf,docx,xlsx,jpg,jpeg,png',
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'File size cannot exceed 10MB.',
            'file.mimes' => 'File must be PDF, DOCX, XLSX, JPG, JPEG, or PNG.',
            'measurement_id.exists' => 'Selected measurement does not exist.',
            'quarter.in' => 'Quarter must be Q1, Q2, Q3, or Q4.',
        ];
    }
}
