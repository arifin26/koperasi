<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExcelExporter
{
    public const CURRENCY_FORMAT = '_("Rp"* #,##0_);_("Rp"* \(#,##0\);_("Rp"* "-"_);_(@_)';
    public const PERCENT_FORMAT = '0.00"%"';

    /**
     * Export Laporan Rekap Simpanan Nasabah ke Excel (.xlsx)
     *
     * @param Collection $data
     * @param array $meta
     * @return StreamedResponse
     */
    public static function exportSavingsRecap(Collection $data, array $meta = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Simpanan');

        // Document Meta Information
        $statusFilter = $meta['statusFilter'] ?? '';
        $statusLabel = match ($statusFilter) {
            'active' => 'Aktif',
            'blacklist' => 'Blacklist',
            default => 'Semua Status',
        };
        $periodeLabel = $meta['periodeLabel'] ?? 'Semua Tanggal';
        $timestamp = $meta['timestamp'] ?? Carbon::now()->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        $userName = $meta['userName'] ?? (auth()->user()->name ?? 'Petugas');

        // Header Banner (Baris 1 - 4)
        $sheet->setCellValue('A1', 'KUD TANI JAYA PUNCU / KOPERASI SWAMITRA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'LAPORAN REKAP SIMPANAN NASABAH');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', "Filter Status: {$statusLabel} | Per Tanggal: {$periodeLabel}");
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

        $sheet->setCellValue('A4', "Dicetak: {$timestamp} oleh {$userName}");
        $sheet->getStyle('A4')->getFont()->setSize(9)->getColor()->setRGB('6C757D');

        // Table Header (Baris 6)
        $headers = [
            'A' => 'No',
            'B' => 'No. Rekening',
            'C' => 'NIK',
            'D' => 'Nama Nasabah',
            'E' => 'Alamat',
            'F' => 'Bunga (%)',
            'G' => 'Status',
            'H' => 'Jml. Transaksi',
            'I' => 'Saldo Simpanan',
            'J' => 'Bunga/Bulan',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}6", $title);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '343A40'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A6:J6')->applyFromArray($headerStyle);
        $sheet->getRowDimension(6)->setRowHeight(26);

        // Data Rows (Baris 7 s.d. N)
        $currentRow = 7;
        foreach ($data as $index => $row) {
            $sheet->setCellValue("A{$currentRow}", $index + 1);

            // No. Rekening & NIK as Explicit String
            $sheet->setCellValueExplicit("B{$currentRow}", (string) ($row->number ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string) ($row->nik ?? '-'), DataType::TYPE_STRING);

            $sheet->setCellValue("D{$currentRow}", $row->name ?? '-');
            $sheet->setCellValue("E{$currentRow}", $row->address ?? '-');

            $rate = (float) ($row->interestRate->rate_percent ?? 0);
            $sheet->setCellValue("F{$currentRow}", $rate);

            $statusText = ($row->status === 'blacklist') ? 'Blacklist' : 'Aktif';
            $sheet->setCellValue("G{$currentRow}", $statusText);

            $txCount = $row->deposits_count ?? $row->total_transaksi ?? 0;
            $sheet->setCellValue("H{$currentRow}", "{$txCount} kali");

            $saldo = (float) ($row->saldo_terakhir ?? 0);
            $sheet->setCellValue("I{$currentRow}", $saldo);

            $bungaBulan = (float) floor($saldo * ($rate / 100) / 12);
            $sheet->setCellValue("J{$currentRow}", $bungaBulan);

            // Row styling
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode(self::PERCENT_FORMAT);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);

            $sheet->getStyle("A{$currentRow}:J{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $currentRow++;
        }

        $lastDataRow = $currentRow - 1;

        // Total Row
        if ($lastDataRow >= 7) {
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", 'Total Dana Simpanan (Hasil Filter):');
            $sheet->setCellValue("I{$currentRow}", "=SUM(I7:I{$lastDataRow})");
            $sheet->setCellValue("J{$currentRow}", "=SUM(J7:J{$lastDataRow})");
        } else {
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", 'Total Dana Simpanan (Hasil Filter):');
            $sheet->setCellValue("I{$currentRow}", 0);
            $sheet->setCellValue("J{$currentRow}", 0);
        }

        $totalStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray($totalStyle);
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getRowDimension($currentRow)->setRowHeight(22);

        // Auto-width for columns A through J
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = date('Y-m-d') . '_laporan_rekap_simpanan_' . time() . '.xlsx';

        return self::createStreamedResponse($spreadsheet, $filename);
    }

    /**
     * Export Laporan Rekap Deposito Berjangka ke Excel (.xlsx)
     *
     * @param Collection $data
     * @param array $meta
     * @return StreamedResponse
     */
    public static function exportDepositRecap(Collection $data, array $meta = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Deposito');

        // Document Meta Information
        $statusFilter = $meta['statusFilter'] ?? '';
        $statusLabel = match ($statusFilter) {
            'active' => 'Aktif',
            'matured' => 'Jatuh Tempo',
            'extended' => 'Diperpanjang',
            'liquidated' => 'Dicairkan',
            default => 'Semua Status',
        };
        $periodeLabel = $meta['periodeLabel'] ?? 'Semua Periode';
        $timestamp = $meta['timestamp'] ?? Carbon::now()->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        $userName = $meta['userName'] ?? (auth()->user()->name ?? 'Petugas');

        // Header Banner (Baris 1 - 4)
        $sheet->setCellValue('A1', 'KUD TANI JAYA PUNCU / KOPERASI SWAMITRA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'LAPORAN REKAP DEPOSITO BERJANGKA');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', "Filter Status: {$statusLabel} | Periode: {$periodeLabel}");
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

        $sheet->setCellValue('A4', "Dicetak: {$timestamp} oleh {$userName}");
        $sheet->getStyle('A4')->getFont()->setSize(9)->getColor()->setRGB('6C757D');

        // Table Header (Baris 6)
        $headers = [
            'A' => 'No',
            'B' => 'Rekening Deposito',
            'C' => 'No. Deposito',
            'D' => 'Nama Nasabah',
            'E' => 'Nominal',
            'F' => 'Bunga/Bulan',
            'G' => 'Rate (%)',
            'H' => 'Tgl. Mulai',
            'I' => 'Jatuh Tempo',
            'J' => 'Status',
            'K' => 'Jml. Transaksi',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}6", $title);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '343A40'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A6:K6')->applyFromArray($headerStyle);
        $sheet->getRowDimension(6)->setRowHeight(26);

        // Data Rows (Baris 7 s.d. N)
        $currentRow = 7;
        foreach ($data as $index => $row) {
            $sheet->setCellValue("A{$currentRow}", $index + 1);

            // Account number & Deposit number as Explicit String
            $sheet->setCellValueExplicit("B{$currentRow}", (string) ($row->account_number ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string) ($row->number ?? '-'), DataType::TYPE_STRING);

            $customerName = $row->customer ? $row->customer->name : '-';
            $sheet->setCellValue("D{$currentRow}", $customerName);

            $sheet->setCellValue("E{$currentRow}", (float) $row->amount);

            $monthlyInterest = (float) ($row->monthly_interest ?? floor($row->amount * ($row->rate_percent / 100) / 12));
            $sheet->setCellValue("F{$currentRow}", $monthlyInterest);

            $sheet->setCellValue("G{$currentRow}", (float) $row->rate_percent);

            $startDateStr = $row->start_date ? Carbon::parse($row->start_date)->format('d/m/Y') : '-';
            $sheet->setCellValue("H{$currentRow}", $startDateStr);

            $maturityDateStr = $row->maturity_date ? Carbon::parse($row->maturity_date)->format('d/m/Y') : '-';
            $sheet->setCellValue("I{$currentRow}", $maturityDateStr);

            $statusText = match ($row->status) {
                'active' => 'Aktif',
                'matured' => 'Jatuh Tempo',
                'extended' => 'Diperpanjang',
                'liquidated' => 'Dicairkan',
                default => ucfirst($row->status ?? '-'),
            };
            $sheet->setCellValue("J{$currentRow}", $statusText);

            $txCount = $row->total_transaksi ?? ($row->customer_id ? \App\Models\Deposit::where('customer_id', $row->customer_id)->count() . ' baris' : '0 baris');
            $sheet->setCellValue("K{$currentRow}", $txCount);

            // Row styling
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$currentRow}")->getNumberFormat()->setFormatCode(self::PERCENT_FORMAT);
            $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A{$currentRow}:K{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $currentRow++;
        }

        $lastDataRow = $currentRow - 1;

        // Total Row
        if ($lastDataRow >= 7) {
            $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", 'Rekap Total (Hasil Filter):');
            $sheet->setCellValue("E{$currentRow}", "=SUM(E7:E{$lastDataRow})");
            $sheet->setCellValue("F{$currentRow}", "=SUM(F7:F{$lastDataRow})");
        } else {
            $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", 'Rekap Total (Hasil Filter):');
            $sheet->setCellValue("E{$currentRow}", 0);
            $sheet->setCellValue("F{$currentRow}", 0);
        }

        $totalStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray($totalStyle);
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getRowDimension($currentRow)->setRowHeight(22);

        // Auto-width for columns A through K
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = date('Y-m-d') . '_laporan_rekap_deposito_' . time() . '.xlsx';

        return self::createStreamedResponse($spreadsheet, $filename);
    }

    /**
     * Helper create StreamedResponse for XLSX
     *
     * @param Spreadsheet $spreadsheet
     * @param string $filename
     * @return StreamedResponse
     */
    private static function createStreamedResponse(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }
}
