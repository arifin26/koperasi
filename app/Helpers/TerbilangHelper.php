<?php

namespace App\Helpers;

class TerbilangHelper
{
    private static $angka = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
    ];

    /**
     * Konversi bilangan bulat menjadi kata-kata (Bahasa Indonesia).
     *
     * @param int|float|string $nominal
     * @param string $suffix
     * @return string
     */
    public static function make($nominal, string $suffix = 'Rupiah'): string
    {
        $nominal = abs((float) $nominal);
        $nominal = floor($nominal);

        if ($nominal == 0) {
            return 'Nol ' . $suffix;
        }

        $hasil = self::convert($nominal);
        return trim($hasil) . ($suffix ? ' ' . $suffix : '');
    }

    private static function convert($nominal): string
    {
        if ($nominal < 12) {
            return ' ' . self::$angka[$nominal];
        } elseif ($nominal < 20) {
            return self::convert($nominal - 10) . ' Belas';
        } elseif ($nominal < 100) {
            return self::convert((int) ($nominal / 10)) . ' Puluh' . self::convert($nominal % 10);
        } elseif ($nominal < 200) {
            return ' Seratus' . self::convert($nominal - 100);
        } elseif ($nominal < 1000) {
            return self::convert((int) ($nominal / 100)) . ' Ratus' . self::convert($nominal % 100);
        } elseif ($nominal < 2000) {
            return ' Seribu' . self::convert($nominal - 1000);
        } elseif ($nominal < 1000000) {
            return self::convert((int) ($nominal / 1000)) . ' Ribu' . self::convert($nominal % 1000);
        } elseif ($nominal < 1000000000) {
            return self::convert((int) ($nominal / 1000000)) . ' Juta' . self::convert($nominal % 1000000);
        } elseif ($nominal < 1000000000000) {
            return self::convert((int) ($nominal / 1000000000)) . ' Milyar' . self::convert(fmod($nominal, 1000000000));
        } elseif ($nominal < 1000000000000000) {
            return self::convert((int) ($nominal / 1000000000000)) . ' Triliun' . self::convert(fmod($nominal, 1000000000000));
        }

        return '';
    }
}
