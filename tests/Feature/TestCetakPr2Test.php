<?php

namespace Tests\Feature;

use App\Services\PassbookPr2;
use Tests\TestCase;

class TestCetakPr2Test extends TestCase
{
    /**
     * Data form valid standar untuk pengujian.
     *
     * @return array<string, mixed>
     */
    protected function getValidPayloadData(): array
    {
        return [
            'mode'               => 'dummy',
            'baris_mulai'        => 0,
            'jumlah_baris'       => 3,
            'seed'               => 1234,
            'margin_atas'        => 20.0,
            'margin_bawah'       => 15.0,
            'margin_kiri'        => 6.0,
            'margin_kanan'       => 6.0,
            'tinggi_baris'       => 4.23,
            'lebar_halaman'      => 150.0,
            'tinggi_halaman'     => 210.0,
            'pitch'              => 10,
            'font'               => '003',
            'unit_vertikal'      => 240,
            'referensi_vertikal' => 'edge',
            'tipe_jilid'         => 'horizontal',
            'mode_keluar'        => 'front',
            'columns'            => PassbookPr2::getDefaultColumns(),
        ];
    }

    /**
     * 1. Halaman GET /test-cetak dapat diakses secara publik tanpa login.
     */
    public function test_akses_halaman_test_cetak_tanpa_autentikasi_berhasil_200(): void
    {
        $response = $this->get(route('test-cetak.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengujian Cetak Passbook Epson PLQ-20');
        $response->assertSee('Emulasi PR2');
        $response->assertSee('PERHATIAN TELLER');
        $response->assertSee('Kendala umum: Notifikasi sukses tapi antrean kosong');
        $response->assertSee('Generic / Text Only');
        $response->assertSee('Pause Printing');
    }
    /**
     * 2. Endpoint POST /test-cetak/payload menghasilkan base64, hex dump, dan saldo berjalan valid.
     */
    public function test_generate_payload_mode_dummy_berhasil(): void
    {
        $data = $this->getValidPayloadData();

        $response = $this->postJson(route('test-cetak.payload'), $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
            'hex_preview',
            'peringatan',
            'grid_info' => [
                'mode',
                'pitch',
                'char_width_mm',
                'lebar_halaman',
                'tinggi_halaman',
                'columns',
                'lines',
            ],
        ]);

        $this->assertEquals('success', $response->json('status'));

        $base64 = $response->json('data');
        $this->assertNotEmpty($base64);

        $binary = base64_decode($base64);
        $this->assertNotEmpty($binary);

        $hexPreview = $response->json('hex_preview');
        $this->assertNotEmpty($hexPreview);
        $this->assertStringContainsString('|', $hexPreview);

        // Verifikasi saldo berjalan pada tiap baris tidak pernah negatif
        $lines = $response->json('grid_info.lines');
        $this->assertCount(3, $lines);
        foreach ($lines as $line) {
            $saldoRaw = str_replace(',', '', $line['data']['SALDO'] ?? '0');
            $this->assertGreaterThanOrEqual(0, (float) $saldoRaw);
        }
    }

    /**
     * 3. Mode kalibrasi menghasilkan karakter penggaris '_' dan deretan digit '0123456789'.
     */
    public function test_generate_payload_mode_kalibrasi_menghasilkan_karakter_garis_dan_digit(): void
    {
        $data = $this->getValidPayloadData();
        $data['mode'] = 'calibration';

        $response = $this->postJson(route('test-cetak.payload'), $data);

        $response->assertStatus(200);

        $binary = base64_decode($response->json('data'));
        $this->assertStringContainsString('_', $binary);
        $this->assertStringContainsString('0123456789', $binary);
    }

    /**
     * 4. Validasi FormRequest menolak nilai batas margin / dimensi yang tidak valid.
     */
    public function test_validasi_error_saat_parameter_melampaui_batas(): void
    {
        $data = $this->getValidPayloadData();
        $data['margin_atas'] = 999;     // melebihi max 100
        $data['lebar_halaman'] = 10;    // kurang dari min 50
        $data['pitch'] = 15;            // bukan 10 atau 12
        $data['mode'] = 'invalid_mode'; // bukan dummy / calibration

        $response = $this->postJson(route('test-cetak.payload'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['margin_atas', 'lebar_halaman', 'pitch', 'mode']);
    }

    /**
     * 5. Deteksi peringatan ketika posisi vertikal baris melewati margin bawah kertas.
     */
    public function test_deteksi_peringatan_saat_baris_melewati_margin_bawah(): void
    {
        $data = $this->getValidPayloadData();
        // Atur tinggi halaman 100 mm, batas bawah 20 mm -> maks Y = 80 mm
        // Margin atas 50 mm, tinggi baris 10 mm, baris 0..4
        // Baris 4: Y = 50 + 4*10 = 90 mm (> 80 mm) -> melewati batas bawah!
        $data['tinggi_halaman'] = 100.0;
        $data['margin_atas'] = 50.0;
        $data['margin_bawah'] = 20.0;
        $data['tinggi_baris'] = 10.0;
        $data['baris_mulai'] = 0;
        $data['jumlah_baris'] = 5;

        $response = $this->postJson(route('test-cetak.payload'), $data);

        $response->assertStatus(200);

        $peringatan = $response->json('peringatan');
        $this->assertNotEmpty($peringatan);

        $hasBottomWarning = false;
        foreach ($peringatan as $warn) {
            if (str_contains($warn, 'melewati batas bawah')) {
                $hasBottomWarning = true;
                break;
            }
        }
        $this->assertTrue($hasBottomWarning, 'Peringatan batas bawah tidak ditemukan.');
    }

    /**
     * 6. Deteksi peringatan ketika teks kolom dipotong karena melewati batas kanan kertas.
     */
    public function test_deteksi_peringatan_saat_teks_melewati_margin_kanan(): void
    {
        $data = $this->getValidPayloadData();
        // Atur lebar halaman sempit 80 mm dengan margin kanan 10 mm
        // Total printable width = 80 - 6 - 10 = 64 mm (~25 karakter pada 10 CPI)
        // Kolom KETERANGAN berada di x = 110 mm yang jauh melewati batas kanan
        $data['lebar_halaman'] = 80.0;
        $data['margin_kanan'] = 10.0;

        $response = $this->postJson(route('test-cetak.payload'), $data);

        $response->assertStatus(200);

        $peringatan = $response->json('peringatan');
        $this->assertNotEmpty($peringatan);

        $hasRightWarning = false;
        foreach ($peringatan as $warn) {
            if (str_contains($warn, 'dipotong karena melewati batas kanan')) {
                $hasRightWarning = true;
                break;
            }
        }
        $this->assertTrue($hasRightWarning, 'Peringatan batas kanan tidak ditemukan.');
    }

    /**
     * 7. Byte header perintah PR2 memuat kode software reset, book mode, binding, pitch, font, dan referensi vertikal.
     */
    public function test_byte_header_pr2_memuat_perintah_reset_book_mode_pitch_dan_font(): void
    {
        $data = $this->getValidPayloadData();
        $data['pitch'] = 10;
        $data['font'] = '003';
        $data['tipe_jilid'] = 'horizontal';
        $data['referensi_vertikal'] = 'edge';

        $response = $this->postJson(route('test-cetak.payload'), $data);
        $response->assertStatus(200);

        $binary = base64_decode($response->json('data'));

        // ESC 0 (1B 30) - Software Reset
        $this->assertStringStartsWith(PassbookPr2::CMD_RESET, $binary);

        // ESC S 5 (1B 53 35) - Passbook Mode
        $this->assertStringContainsString(PassbookPr2::CMD_BOOK_MODE, $binary);

        // ESC ' F (1B 27 46) - Binding Horizontal
        $this->assertStringContainsString(PassbookPr2::CMD_BINDING_HORIZ, $binary);

        // ESC < (1B 3C) - Pitch 10 CPI
        $this->assertStringContainsString(PassbookPr2::CMD_PITCH_10CPI, $binary);

        // ESC R 003 (1B 52 30 30 33) - Font 003 NLQ1
        $this->assertStringContainsString("\x1B\x52003", $binary);

        // ESC # 0 (1B 23 30) - Referensi Vertikal Tepi Atas
        $this->assertStringContainsString(PassbookPr2::CMD_VERT_REF_EDGE, $binary);
    }

    /**
     * 8. Mode keluar buku (eject) menghasilkan byte ESC O (front) atau FF (rear).
     */
    public function test_mode_keluar_buku_eject(): void
    {
        // 8a. Eject Depan (ESC O)
        $dataFront = $this->getValidPayloadData();
        $dataFront['mode_keluar'] = 'front';
        $resFront = $this->postJson(route('test-cetak.payload'), $dataFront);
        $binFront = base64_decode($resFront->json('data'));
        $this->assertStringContainsString(PassbookPr2::CMD_EJECT_FRONT, $binFront);

        // 8b. Eject Belakang (FF 0C)
        $dataRear = $this->getValidPayloadData();
        $dataRear['mode_keluar'] = 'rear';
        $resRear = $this->postJson(route('test-cetak.payload'), $dataRear);
        $binRear = base64_decode($resRear->json('data'));
        $this->assertStringContainsString(PassbookPr2::CMD_EJECT_REAR, $binRear);

        // 8c. None (Tidak ada Eject)
        $dataNone = $this->getValidPayloadData();
        $dataNone['mode_keluar'] = 'none';
        $resNone = $this->postJson(route('test-cetak.payload'), $dataNone);
        $binNone = base64_decode($resNone->json('data'));
        $this->assertStringNotContainsString(PassbookPr2::CMD_EJECT_FRONT, $binNone);
        $this->assertStringNotContainsString(PassbookPr2::CMD_EJECT_REAR, $binNone);
    }

    /**
     * 9. Endpoint digital signing QZ Tray mengembalikan 204 No Content saat private key belum dikonfigurasi.
     */
    public function test_sign_certificate_endpoint_fallback_no_content(): void
    {
        $response = $this->post(route('test-cetak.sign'), ['request' => 'string_to_sign']);

        $response->assertStatus(204);
    }
}
