<?php

namespace App\Http\Requests;

use App\Services\PassbookPr2;
use Illuminate\Foundation\Http\FormRequest;

class TestCetakPayloadRequest extends FormRequest
{
    /**
     * Tentukan otorisasi request (tanpa login).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Persiapkan data sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('columns') || !is_array($this->input('columns'))) {
            $this->merge([
                'columns' => PassbookPr2::getDefaultColumns(),
            ]);
        }

        if (!$this->has('mode')) {
            $this->merge(['mode' => 'dummy']);
        }
    }

    /**
     * Aturan validasi payload cetak passbook PR2.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode'                => 'required|in:dummy,calibration',
            'baris_mulai'         => 'required|integer|min:0|max:40',
            'jumlah_baris'        => 'required|integer|min:1|max:30',
            'seed'                => 'nullable|integer',
            'margin_atas'         => 'required|numeric|between:-20,100',
            'margin_bawah'        => 'required|numeric|between:0,100',
            'margin_kiri'         => 'required|numeric|between:-20,100',
            'margin_kanan'        => 'required|numeric|between:0,100',
            'tinggi_baris'        => 'required|numeric|between:2,10',
            'lebar_halaman'       => 'required|numeric|between:50,300',
            'tinggi_halaman'      => 'required|numeric|between:50,400',
            'pitch'               => 'required|in:10,12',
            'font'                => 'required|in:000,003,014,017',
            'unit_vertikal'       => 'required|in:216,240',
            'referensi_vertikal'  => 'required|in:edge,tof',
            'tipe_jilid'          => 'required|in:horizontal,vertical',
            'mode_keluar'         => 'required|in:none,front,rear',
            'columns'             => 'required|array|size:7',
            'columns.*.name'      => 'required|string',
            'columns.*.x'         => 'required|numeric|min:0|max:300',
            'columns.*.width'     => 'required|numeric|min:1|max:300',
            'columns.*.align'     => 'nullable|in:left,right',
        ];
    }

    /**
     * Validasi tambahan setelah validasi dasar lulus.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $marginKiri = $this->input('margin_kiri');
            if (is_numeric($marginKiri)) {
                $fineOffset = round(((float) $marginKiri / 25.4) * 120);
                if ($fineOffset >= 1000 || $fineOffset < 0) {
                    $validator->errors()->add('margin_kiri', 'Offset halus PR2 (1/120") harus berada pada rentang 0-999.');
                }
            }

            $tinggiHalaman = $this->input('tinggi_halaman');
            $unitVertikal = $this->input('unit_vertikal');
            if (is_numeric($tinggiHalaman) && is_numeric($unitVertikal)) {
                $vertUnits = round(((float) $tinggiHalaman / 25.4) * (int) $unitVertikal);
                if ($vertUnits >= 10000) {
                    $validator->errors()->add('tinggi_halaman', 'Posisi vertikal PR2 tidak boleh melebihi 9999 unit.');
                }
            }

            $pitch = (int) $this->input('pitch', 10);
            $charWidth = $pitch > 0 ? (25.4 / $pitch) : 2.54;
            $columns = $this->input('columns', []);
            if (is_array($columns)) {
                foreach ($columns as $index => $col) {
                    if (isset($col['x']) && is_numeric($col['x'])) {
                        $charPos = round((float) $col['x'] / $charWidth);
                        if ($charPos >= 1000) {
                            $validator->errors()->add("columns.{$index}.x", 'Posisi karakter kolom tidak boleh melebihi 999.');
                        }
                    }
                }
            }
        });
    }
}
