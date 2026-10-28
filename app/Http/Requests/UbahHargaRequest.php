<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahHargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'harga' => ['required', 'array', 'min:1'],
            'harga.*' => ['required', 'integer', 'min:0', 'max:100000000'],
            'aktif' => ['nullable', 'array'],
            'aktif.*' => ['boolean'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            $this->redirector->back()
                ->withErrors(['server' => $validator->errors()->first()])
                ->withInput()
        );
    }
}
