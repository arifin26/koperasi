<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCetakPayloadRequest;
use App\Services\PassbookPr2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TestCetakController extends Controller
{
    /**
     * Tampilkan halaman pengujian cetak passbook PLQ-20 PR2.
     *
     * @return View
     */
    public function index(): View
    {
        $defaultConfig = [
            'mode'               => 'dummy',
            'baris_mulai'        => 0,
            'jumlah_baris'       => 5,
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

        return view('pages.test-cetak.index', [
            'title'   => 'Pengujian Cetak Passbook Epson PLQ-20 (Emulasi PR2)',
            'default' => $defaultConfig,
        ]);
    }

    /**
     * Generate raw binary payload PR2, hex dump, dan metadata grid.
     *
     * @param TestCetakPayloadRequest $request
     * @return JsonResponse
     */
    public function generatePayload(TestCetakPayloadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = PassbookPr2::buildPayload($validated);

        return response()->json([
            'status'      => 'success',
            'data'        => $result['base64'],
            'hex_preview' => $result['hex_preview'],
            'peringatan'  => $result['peringatan'],
            'grid_info'   => $result['grid_info'],
        ]);
    }

    /**
     * Endpoint penandatanganan digital sertifikat QZ Tray (opsional).
     * Jika private key tidak ditemukan, kembalikan 204 No Content agar QZ Tray
     * fallback ke mode interaktif unverified.
     *
     * @param Request $request
     * @return Response
     */
    public function signCertificate(Request $request): Response
    {
        $toSign = $request->getContent();
        if (empty($toSign) && $request->has('request')) {
            $toSign = (string) $request->input('request');
        }

        $privateKey = null;

        $keyPath = env('QZ_PRIVATE_KEY_PATH');
        if (!empty($keyPath) && file_exists($keyPath)) {
            $privateKey = file_get_contents($keyPath);
        }

        if (empty($privateKey)) {
            $rawKey = env('QZ_PRIVATE_KEY');
            if (!empty($rawKey)) {
                $privateKey = $rawKey;
            }
        }

        if (empty($privateKey) || empty($toSign)) {
            return response()->noContent();
        }

        $signature = '';
        $pkeyResource = openssl_pkey_get_private($privateKey);

        if ($pkeyResource && openssl_sign($toSign, $signature, $pkeyResource, OPENSSL_ALGO_SHA512)) {
            if (is_resource($pkeyResource)) {
                openssl_free_key($pkeyResource);
            }
            return response(base64_encode($signature), 200, [
                'Content-Type' => 'text/plain',
            ]);
        }

        return response()->noContent();
    }
}
