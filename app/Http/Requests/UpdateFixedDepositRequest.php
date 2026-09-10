<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFixedDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $deposit = $this->route('fixed_deposit') ?? $this->route('deposito');
        $depositId = $deposit instanceof \App\Models\FixedDeposit ? $deposit->id : $deposit;

        return [
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fixed_deposits', 'account_number')->ignore($depositId)->whereNull('deleted_at'),
            ],
            'amount' => 'required|integer|min:1000000',
            'tenor_months' => 'required|integer|min:1|max:120',
            'rate_percent' => 'required|numeric|between:0,100',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'account_number.required' => 'Nomor rekening deposito harus diisi',
            'account_number.unique' => 'Nomor rekening deposito sudah terdaftar',
            'amount.required' => 'Nominal harus diisi',
            'amount.min' => 'Nominal minimal Rp 1.000.000',
            'tenor_months.required' => 'Tenor harus diisi',
            'tenor_months.min' => 'Tenor minimal 1 bulan',
            'tenor_months.max' => 'Tenor maksimal 120 bulan',
            'rate_percent.required' => 'Suku bunga harus diisi',
            'rate_percent.between' => 'Suku bunga harus antara 0-100%',
        ];
    }
}
