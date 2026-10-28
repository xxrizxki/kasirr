<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'alpha_dash', 'lowercase', Rule::unique('categories', 'kode')],
            'nama' => ['required', 'string', 'max:50'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'urutan' => $this->urutan ?? 0,
        ]);
    }
}
