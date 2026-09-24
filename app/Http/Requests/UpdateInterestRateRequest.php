<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterestRateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'required|in:simpanan,deposito',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Jenis bunga harus dipilih (Simpanan / Deposito)',
            'type.in' => 'Jenis bunga harus simpanan atau deposito',
            'rate_percent.required' => 'Rate suku bunga harus diisi',
            'rate_percent.numeric' => 'Rate suku bunga harus berupa angka',
            'rate_percent.min' => 'Rate suku bunga minimal 0%',
            'rate_percent.max' => 'Rate suku bunga maksimal 100%',
            'effective_date.required' => 'Tanggal mulai berlaku harus diisi',
            'effective_date.date' => 'Format tanggal mulai berlaku tidak valid',
        ];
    }
}
