<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStrategicAdvisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File PDF wajib diunggah.',
            'file.mimes'    => 'Hanya file PDF yang didukung.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ];
    }
}