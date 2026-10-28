<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.size_id' => 'required|integer|exists:sizes,id',
            'items.*.qty' => 'required|integer|min:1|max:999',
            'nama_pembeli' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:140',
        ];
    }
}
