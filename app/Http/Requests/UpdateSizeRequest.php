<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'nama' => ['required', 'string', 'max:20', Rule::unique('sizes')->where('category_id', $this->category_id)->ignore($this->size)],
            'harga' => ['required', 'integer', 'min:0', 'max:100000000'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'urutan' => $this->urutan ?? 0,
            'is_active' => $this->boolean('is_active', false),
        ]);
    }
}
