<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInitiativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measurement_id' => 'required|exists:measurements,id',
            'initiative' => 'required|string|max:1000',
        ];
    }
}
