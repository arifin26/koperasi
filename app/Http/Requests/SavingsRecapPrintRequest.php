<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavingsRecapPrintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'nullable|in:active,blacklist',
            'tanggal_dari' => 'nullable|date_format:Y-m-d',
            'tanggal_sampai' => 'nullable|date_format:Y-m-d|after_or_equal:tanggal_dari',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'status.in' => 'Status nasabah tidak valid.',
            'tanggal_dari.date_format' => 'Format tanggal awal tidak valid. Gunakan format YYYY-MM-DD.',
            'tanggal_sampai.date_format' => 'Format tanggal akhir tidak valid. Gunakan format YYYY-MM-DD.',
            'tanggal_sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
        ];
    }
}
