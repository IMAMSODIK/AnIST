<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskAdvisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question'    => ['required', 'string', 'min:5', 'max:2000'],
            'session_id'  => ['nullable', 'integer', 'exists:advisor_sessions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Pertanyaan wajib diisi.',
            'question.min'      => 'Pertanyaan minimal 5 karakter.',
            'question.max'      => 'Pertanyaan maksimal 2000 karakter.',
        ];
    }
}
