<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFixedDepositRequest extends FormRequest
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
        return [
            'customer_id' => 'required|exists:customers,id',
            'interest_rate_id' => 'nullable|exists:interest_rates,id',
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fixed_deposits', 'account_number')->whereNull('deleted_at'),
            ],
            'amount' => 'required|integer|min:1000000',
            'tenor_months' => 'required|integer|min:1|max:120',
            'rate_percent' => 'required|numeric|between:0,100',
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
            'customer_id.required' => 'Nasabah harus dipilih',
            'customer_id.exists' => 'Nasabah tidak ditemukan',
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

    /**
     * Custom validation after basic rules pass.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customer = \App\Models\Customer::find($this->customer_id);

            if (!$customer) {
                return;
            }

            // Validation 1: Account number MUST NOT equal customer.number (savings account)
            if ($this->account_number === $customer->number) {
                $validator->errors()->add(
                    'account_number',
                    'Nomor rekening deposito tidak boleh sama dengan nomor rekening simpanan (' . $customer->number . '). Gunakan nomor rekening yang berbeda.'
                );
            }

            // Validation 2: Check if customer already has active/extended deposit
            // If so, block creation and ask to extend or liquidate existing one
            $existingActive = \App\Models\FixedDeposit::where('customer_id', $customer->id)
                ->whereIn('status', ['active', 'extended'])
                ->whereNull('deleted_at')
                ->first();

            if ($existingActive) {
                $validator->errors()->add(
                    'customer_id',
                    'Nasabah ini sudah memiliki deposito aktif (' . $existingActive->number . '). ' .
                    'Silakan cairkan atau perpanjang deposito yang ada terlebih dahulu sebelum membuka deposito baru.'
                );
            }
        });
    }
}
