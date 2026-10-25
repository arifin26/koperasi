<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Pengujian Cetak Passbook PLQ-20 (PR2)' }}</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f6f9;
            --card-border: #e2e8f0;
            --accent-color: #0d6efd;
            --font-mono: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
        body {
            background-color: var(--bg-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 0.92rem;
            color: #334155;
        }
        .navbar-brand-badge {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .card {
            border: 1px solid var(--card-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 1.25rem;
            background: #ffffff;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--card-border);
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header i {
            color: #64748b;
            margin-right: 6px;
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.25rem;
        }
        .form-control-sm, .form-select-sm {
            border-radius: 6px;
            border-color: #cbd5e1;
            font-size: 0.85rem;
        }
        .form-control-sm:focus, .form-select-sm:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }
        .input-group-text-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            background: #f1f5f9;
            color: #64748b;
        }
        .column-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            transition: background 0.15s ease;
        }
        .column-row:hover {
            background: #f1f5f9;
        }
        .column-badge {
            font-family: var(--font-mono);
            font-size: 0.78rem;
            font-weight: 700;
            color: #1e293b;
        }
        .alert-instruction {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 8px;
            font-size: 0.88rem;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-dot.connected {
            background-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        }
        .status-dot.disconnected {
            background-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
        }
        .preview-svg-container {
            background: #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 480px;
            overflow: auto;
        }
        .hexdump-viewer {
            background: #0f172a;
            color: #e2e8f0;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            line-height: 1.45;
            padding: 0.85rem;
            border-radius: 8px;
            max-height: 280px;
            overflow: auto;
            white-space: pre;
            margin: 0;
        }
        .btn-action {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        .floating-actions {
            position: sticky;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-top: 1px solid #e2e8f0;
            padding: 0.75rem 0;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="navbar bg-white border-bottom shadow-sm px-3 py-2 sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-print text-primary fs-5"></i>
                <span class="fw-bold fs-6">Pengujian Cetak Passbook Epson PLQ-20</span>
                <span class="navbar-brand-badge">Emulasi PR2</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center bg-light border px-3 py-1 rounded-pill">
                    <span id="qz-status-dot" class="status-dot disconnected"></span>
                    <span id="qz-status-text" class="small fw-semibold text-secondary">QZ Tray: Tidak Terhubung</span>
                </div>
                <button type="button" id="btn-connect-qz" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-plug me-1"></i> Sambungkan QZ Tray
                </button>
            </div>
        </div>
    </header>

    <main class="container-fluid py-3">
        <!-- Banner Instruksi Petunjuk Wajib PLQ-20 -->
        <div class="alert alert-instruction d-flex align-items-start gap-3 shadow-sm mb-3">
            <i class="fa-solid fa-triangle-exclamation fs-4 mt-1 flex-shrink-0"></i>
            <div>
                <strong>PERHATIAN TELLER:</strong> Masukkan buku tabungan ke dalam slot printer sebelum menekan tombol Cetak!
                Perintah posisi (<code>ESC H</code>, <code>ESC / 1</code>, <code>CR</code>, <code>FF</code>) akan diabaikan oleh printer passbook Epson PLQ-20 jika sensor mekanis mendeteksi media kertas belum dimasukkan.
            </div>
        </div>

        <!-- Alert QZ Tray Not Running Warning (Disembunyikan jika terhubung) -->
        <div id="qz-alert-warning" class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
                <span>
                    <strong>QZ Tray belum aktif di PC ini.</strong> Pastikan aplikasi desktop <em>QZ Tray</em> telah terpasang dan berjalan di system tray Windows/Linux/macOS teller Anda (Port default WebSocket 8182 / 8181).
                </span>
            </div>
            <a href="https://qz.io/download/" target="_blank" class="btn btn-sm btn-outline-danger text-nowrap ms-2">
                <i class="fa-solid fa-download me-1"></i> Unduh QZ Tray
            </a>
        </div>

        <!-- Baris Status Printer -->
        <div class="card mb-3">
            <div class="card-body py-2 px-3">
                <div class="row align-items-center g-2">
                    <div class="col-md-2">
                        <label for="printer-select" class="form-label mb-0 fw-bold">
                            <i class="fa-solid fa-fax text-secondary me-1"></i> Pilih Printer:
                        </label>
                    </div>
                    <div class="col-md-7">
                        <select id="printer-select" class="form-select form-select-sm">
                            <option value="">-- Menunggu koneksi QZ Tray... --</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" id="btn-reload-printers" class="btn btn-sm btn-light border flex-grow-1" disabled>
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Muat Ulang Printer
                        </button>
                        <button type="button" id="btn-test-ping-qz" class="btn btn-sm btn-light border" title="Cek status koneksi">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </button>
                    </div>
                </div>

                <!-- Accordion / Bantuan Ringkas Troubleshooting Spooler -->
                <details class="mt-2 pt-2 border-top text-muted small" style="cursor: pointer;">
                    <summary class="fw-semibold text-primary user-select-none">
                        <i class="fa-solid fa-circle-question me-1"></i> Kendala umum: Notifikasi sukses tapi antrean kosong / printer tidak bergerak? (Klik untuk solusi)
                    </summary>
                    <div class="mt-2 p-2 rounded bg-light border text-dark">
                        <div class="fw-bold mb-1 text-danger"><i class="fa-solid fa-clipboard-check me-1"></i> Checklist 5 Penyebab Utama Antrean Kosong di Windows:</div>
                        <ol class="ps-3 mb-1">
                            <li class="mb-1"><strong>Antrean Selesai Terlalu Cepat (&lt;0.1 dtk) / Uji Jeda Spooler:</strong> Payload RAW PR2 sangat kecil (~300–500 byte). Buka <em>Control Panel &gt; Devices &amp; Printers &gt; See what's printing &gt; Menu Printer &gt; <strong>Pause Printing</strong></em>. Lalu klik tombol cetak. Jika dokumen muncul tertahan berstatus <em>Paused</em>, berarti data 100% masuk ke Windows Spooler.</li>
                            <li class="mb-1"><strong>Wajib Driver "Generic / Text Only":</strong> Jika menggunakan driver grafis resmi Epson (GDI), Windows memfilter dan membuang byte RAW Olivetti PR2 tanpa mencetak. Tambahkan printer baru dengan driver <code>Generic / Text Only</code> terhubung ke port USB printer.</li>
                            <li class="mb-1"><strong>Pemetaan Port USB Salah:</strong> Di <em>Printer Properties &gt; tab Ports</em>, pastikan port USB yang dicentang adalah port aktif printer PLQ-20 (misal <code>USB001</code> atau <code>USB002</code>), bukan COM/LPT/FILE.</li>
                            <li class="mb-1"><strong>Sensor Kertas PLQ-20 (Auto Sheet Alignment):</strong> Masukkan buku tabungan/kertas ke mulut depan printer sampai ditarik motor dan lampu <strong>Ready</strong> menyala stabil sebelum klik tombol cetak. PLQ-20 menolak print job jika kertas belum dimasukkan.</li>
                            <li class="mb-1"><strong>Emulasi Hardware Printer Harus PR2:</strong> Matikan printer, tahan tombol <strong>F1 + F2</strong>, nyalakan printer, masukkan kertas A4, lalu pastikan setting <em>Emulation</em> adalah <strong>PR2</strong> (bukan ESC/P2 atau IBM).</li>
                        </ol>
                        <div class="mt-2 fst-italic text-secondary">
                            <i class="fa-solid fa-book me-1"></i> Panduan lengkap teknisi dapat dibaca pada file <code>PANDUAN-LANGKAH-2-DRIVER-DAN-SPOOLER-WINDOWS.md</code> di repositori.
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="row g-3">
            <!-- Kolom Kiri: Form Pengaturan FormRequest -->
            <div class="col-lg-6 col-xl-5">
                <form id="form-test-cetak">
                    <!-- Card 1: Dimensi Kertas & Margin (mm) -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fa-solid fa-ruler-combined"></i> Dimensi Halaman & Margin (mm)</span>
                            <span class="badge bg-light text-muted fw-normal">Satuan: milimeter</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">Lebar Halaman</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="50" max="300" class="form-control" name="lebar_halaman" id="lebar_halaman" value="{{ $default['lebar_halaman'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tinggi Halaman</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="50" max="400" class="form-control" name="tinggi_halaman" id="tinggi_halaman" value="{{ $default['tinggi_halaman'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-3">
                                    <label class="form-label">Margin Atas</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="-20" max="100" class="form-control" name="margin_atas" id="margin_atas" value="{{ $default['margin_atas'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Margin Kiri</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="-20" max="100" class="form-control" name="margin_kiri" id="margin_kiri" value="{{ $default['margin_kiri'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Margin Kanan</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="0" max="100" class="form-control" name="margin_kanan" id="margin_kanan" value="{{ $default['margin_kanan'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Margin Bawah</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="0" max="100" class="form-control" name="margin_bawah" id="margin_bawah" value="{{ $default['margin_bawah'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Baris & Cetak -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fa-solid fa-list-ol"></i> Baris & Posisi Cetak</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-3">
                                    <label class="form-label">Baris Mulai</label>
                                    <input type="number" min="0" max="40" class="form-control form-control-sm" name="baris_mulai" id="baris_mulai" value="{{ $default['baris_mulai'] }}" title="0 = Baris paling atas">
                                    <small class="text-muted" style="font-size:0.7rem">0 = Baris pertama</small>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Jumlah Baris</label>
                                    <input type="number" min="1" max="30" class="form-control form-control-sm" name="jumlah_baris" id="jumlah_baris" value="{{ $default['jumlah_baris'] }}">
                                    <small class="text-muted" style="font-size:0.7rem">Maks 30 baris</small>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Tinggi Baris</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="2" max="10" class="form-control" name="tinggi_baris" id="tinggi_baris" value="{{ $default['tinggi_baris'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                    <small class="text-muted" style="font-size:0.7rem">Std: 4.23 mm (1/6")</small>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Seed Acak</label>
                                    <input type="number" class="form-control form-control-sm" name="seed" id="seed" value="{{ $default['seed'] }}" placeholder="Opsional">
                                    <small class="text-muted" style="font-size:0.7rem">U/ data acak sama</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Parameter Printer PR2 -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fa-solid fa-microchip"></i> Parameter Emulasi PR2 (PLQ-20)</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label">Pitch Karakter</label>
                                    <select class="form-select form-select-sm" name="pitch" id="pitch">
                                        <option value="10" {{ $default['pitch'] == 10 ? 'selected' : '' }}>10 CPI (ESC &lt;) - 2.54 mm</option>
                                        <option value="12" {{ $default['pitch'] == 12 ? 'selected' : '' }}>12 CPI (ESC =) - 2.12 mm</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Font PR2</label>
                                    <select class="form-select form-select-sm" name="font" id="font">
                                        <option value="000" {{ $default['font'] == '000' ? 'selected' : '' }}>000 - Draft</option>
                                        <option value="003" {{ $default['font'] == '003' ? 'selected' : '' }}>003 - NLQ 1 (Standard)</option>
                                        <option value="014" {{ $default['font'] == '014' ? 'selected' : '' }}>014 - NLQ 2</option>
                                        <option value="017" {{ $default['font'] == '017' ? 'selected' : '' }}>017 - LQ 2 (High Res)</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Unit Vertikal</label>
                                    <select class="form-select form-select-sm" name="unit_vertikal" id="unit_vertikal">
                                        <option value="240" {{ $default['unit_vertikal'] == 240 ? 'selected' : '' }}>240 (1/240" - Default)</option>
                                        <option value="216" {{ $default['unit_vertikal'] == 216 ? 'selected' : '' }}>216 (1/216")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label">Ref. Vertikal</label>
                                    <select class="form-select form-select-sm" name="referensi_vertikal" id="referensi_vertikal">
                                        <option value="edge" {{ $default['referensi_vertikal'] == 'edge' ? 'selected' : '' }}>Tepi Atas (ESC # 0)</option>
                                        <option value="tof" {{ $default['referensi_vertikal'] == 'tof' ? 'selected' : '' }}>Top of Form (ESC # 1)</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Tipe Jilid Buku</label>
                                    <select class="form-select form-select-sm" name="tipe_jilid" id="tipe_jilid">
                                        <option value="horizontal" {{ $default['tipe_jilid'] == 'horizontal' ? 'selected' : '' }}>Horizontal (ESC ' F)</option>
                                        <option value="vertical" {{ $default['tipe_jilid'] == 'vertical' ? 'selected' : '' }}>Vertikal (ESC ' D)</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Mode Keluar Buku</label>
                                    <select class="form-select form-select-sm" name="mode_keluar" id="mode_keluar">
                                        <option value="front" {{ $default['mode_keluar'] == 'front' ? 'selected' : '' }}>ESC O (Depan/Teller)</option>
                                        <option value="rear" {{ $default['mode_keluar'] == 'rear' ? 'selected' : '' }}>FF 0C (Belakang)</option>
                                        <option value="none" {{ $default['mode_keluar'] == 'none' ? 'selected' : '' }}>Tidak Keluar (Diam)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Koordinat 7 Kolom Buku Tabungan -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fa-solid fa-table-columns"></i> Koordinat 7 Kolom Buku Tabungan</span>
                            <span class="badge bg-light text-muted fw-normal">X mm dihitung dari Margin Kiri</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between px-2 mb-1 small fw-bold text-muted">
                                <span style="width: 28%">NAMA KOLOM</span>
                                <span style="width: 25%">POSISI X (mm)</span>
                                <span style="width: 25%">LEBAR (mm)</span>
                                <span style="width: 22%">PERATAAN</span>
                            </div>

                            @foreach($default['columns'] as $idx => $col)
                            <div class="column-row d-flex align-items-center gap-2">
                                <div style="width: 28%">
                                    <input type="hidden" name="columns[{{ $idx }}][name]" value="{{ $col['name'] }}">
                                    <span class="column-badge">{{ $col['name'] }}</span>
                                </div>
                                <div style="width: 25%">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="0" max="300" class="form-control col-x-input" name="columns[{{ $idx }}][x]" id="col_{{ $idx }}_x" value="{{ $col['x'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div style="width: 25%">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="1" max="300" class="form-control col-width-input" name="columns[{{ $idx }}][width]" id="col_{{ $idx }}_width" value="{{ $col['width'] }}">
                                        <span class="input-group-text input-group-text-sm">mm</span>
                                    </div>
                                </div>
                                <div style="width: 22%">
                                    <select class="form-select form-select-sm" name="columns[{{ $idx }}][align]">
                                        <option value="left" {{ ($col['align'] ?? 'left') == 'left' ? 'selected' : '' }}>Kiri</option>
                                        <option value="right" {{ ($col['align'] ?? 'left') == 'right' ? 'selected' : '' }}>Kanan</option>
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            <!-- Kolom Kanan: Pratinjau Interaktif Diagram Kertas & Hex Viewer -->
            <div class="col-lg-6 col-xl-7">
                <!-- Box Peringatan Dinamis -->
                <div id="box-warnings" class="alert alert-warning shadow-sm d-none mb-3">
                    <div class="fw-bold d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Peringatan Pembatas / Pemotongan Margin:</span>
                    </div>
                    <ul id="list-warnings" class="mb-0 ps-3 small"></ul>
                </div>

                <!-- Card Diagram Pratinjau Skala Kertas -->
                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="fa-solid fa-eye"></i> Pratinjau Tata Letak Kertas Passbook (Skala mm)</span>
                        <div class="d-flex align-items-center gap-2">
                            <span id="preview-grid-meta" class="small text-muted font-monospace">--</span>
                            <button type="button" id="btn-refresh-preview" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Segarkan Pratinjau">
                                <i class="fa-solid fa-rotate-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="preview-svg-container">
                            <svg id="svg-passbook-preview" width="100%" height="450" viewBox="0 0 160 220" xmlns="http://www.w3.org/2000/svg">
                                <!-- Konten SVG di-generate secara dinamis oleh JavaScript -->
                            </svg>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted" style="font-size:0.75rem;">
                            <div>
                                <span class="badge" style="background:#cbd5e1; color:#334155;">Margin</span>
                                <span class="badge" style="background:#e0f2fe; color:#0369a1;">Area Cetak</span>
                                <span class="badge" style="background:#22c55e; color:#ffffff;">Baris Aktif</span>
                                <span class="badge" style="background:#ef4444; color:#ffffff;">Terpotong Batas</span>
                            </div>
                            <div>
                                <span>Posisikan kursor pada baris/kolom untuk rincian mm & grid karakter</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Hex Dump Viewer -->
                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="fa-solid fa-code"></i> Inspeksi Perintah Byte PR2 (Hex Dump Canonical)</span>
                        <div class="d-flex align-items-center gap-2">
                            <span id="hex-bytes-count" class="badge bg-secondary font-monospace">0 bytes</span>
                            <button type="button" id="btn-copy-hex" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Salin Hex Dump">
                                <i class="fa-regular fa-copy me-1"></i> Salin Hex
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <pre id="hexdump-code" class="hexdump-viewer"><code>Menunggu pembuatan payload...</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Toolbar di Bagian Bawah -->
    <div class="floating-actions">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-2 px-4">
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btn-reset-defaults" class="btn btn-outline-danger btn-action">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset ke Default
                </button>
                <button type="button" id="btn-download-bin" class="btn btn-outline-success btn-action">
                    <i class="fa-solid fa-file-arrow-down me-1"></i> Unduh Payload (.bin)
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btn-print-calibration" class="btn btn-warning btn-action text-dark">
                    <i class="fa-solid fa-ruler-horizontal me-1"></i> Cetak Tes Kalibrasi
                </button>
                <button type="button" id="btn-print-dummy" class="btn btn-primary btn-action shadow">
                    <i class="fa-solid fa-print me-1"></i> Cetak Data Dummy ke PLQ-20
                </button>
            </div>
        </div>
    </div>

    <!-- Data bawaan default untuk pemulihan (JSON) -->
    <div id="default-config-data" class="d-none" data-json="{{ json_encode($default) }}"></div>

    <!-- QZ Tray Official JavaScript Library via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>

    <script>
        (function() {
            'use strict';

            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const STORAGE_KEY = 'plq20_pr2_test_config_v1';
            const SELECTED_PRINTER_KEY = 'plq20_selected_printer';

            const defaultConfigEl = document.getElementById('default-config-data');
            const defaultValues = defaultConfigEl ? JSON.parse(defaultConfigEl.dataset.json) : {};

            // Elemen UI
            const form = document.getElementById('form-test-cetak');
            const qzStatusDot = document.getElementById('qz-status-dot');
            const qzStatusText = document.getElementById('qz-status-text');
            const qzAlertWarning = document.getElementById('qz-alert-warning');
            const btnConnectQz = document.getElementById('btn-connect-qz');
            const printerSelect = document.getElementById('printer-select');
            const btnReloadPrinters = document.getElementById('btn-reload-printers');
            const btnTestPingQz = document.getElementById('btn-test-ping-qz');

            const svgPreview = document.getElementById('svg-passbook-preview');
            const previewGridMeta = document.getElementById('preview-grid-meta');
            const hexdumpCode = document.getElementById('hexdump-code');
            const hexBytesCount = document.getElementById('hex-bytes-count');
            const btnCopyHex = document.getElementById('btn-copy-hex');
            const boxWarnings = document.getElementById('box-warnings');
            const listWarnings = document.getElementById('list-warnings');

            const btnPrintDummy = document.getElementById('btn-print-dummy');
            const btnPrintCalibration = document.getElementById('btn-print-calibration');
            const btnDownloadBin = document.getElementById('btn-download-bin');
            const btnResetDefaults = document.getElementById('btn-reset-defaults');
            const btnRefreshPreview = document.getElementById('btn-refresh-preview');

            let currentPayloadBase64 = '';
            let currentHexDump = '';
            let currentGridInfo = null;
            let currentWarnings = [];
            let isQzConnected = false;
            let debounceTimer = null;

            // 1. Inisialisasi QZ Tray Security Certificate Signing (Fallback jika ada server key)
            if (window.qz) {
                qz.security.setSignaturePromise(function(toSign) {
                    return function(resolve, reject) {
                        fetch('{{ route("test-cetak.sign") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'text/plain',
                                'X-CSRF-TOKEN': CSRF_TOKEN
                            },
                            body: toSign
                        })
                        .then(response => {
                            if (response.status === 200) {
                                return response.text();
                            }
                            // Tidak ada private key di server: return null agar popup unverified dialog muncul
                            return null;
                        })
                        .then(signature => {
                            if (signature) {
                                resolve(signature);
                            } else {
                                resolve(); // continue unverified
                            }
                        })
                        .catch(() => resolve());
                    };
                });
            }

            // 2. Fungsi Koneksi QZ Tray
            async function connectQzTray(showSuccessNotice = false) {
                if (!window.qz) {
                    alert('Pustaka QZ Tray JS belum termuat. Periksa koneksi internet Anda.');
                    return;
                }

                if (qz.websocket.isActive()) {
                    setQzStatus(true);
                    await populatePrinters();
                    if (showSuccessNotice) alert('QZ Tray sudah terhubung.');
                    return;
                }

                qzStatusText.textContent = 'Menghubungkan ke QZ Tray...';
                try {
                    await qz.websocket.connect({ retries: 2, delay: 1 });
                    setQzStatus(true);
                    await populatePrinters();
                    if (showSuccessNotice) alert('Berhasil terhubung ke QZ Tray!');
                } catch (err) {
                    console.warn('Gagal terhubung ke QZ Tray:', err);
                    setQzStatus(false);
                    if (showSuccessNotice) {
                        alert('Gagal terhubung ke QZ Tray di PC Anda.\n\nPastikan aplikasi QZ Tray telah berjalan di latar belakang.');
                    }
                }
            }

            function setQzStatus(connected) {
                isQzConnected = connected;
                if (connected) {
                    qzStatusDot.className = 'status-dot connected';
                    qzStatusText.textContent = 'QZ Tray: Terhubung';
                    qzStatusText.className = 'small fw-semibold text-success';
                    qzAlertWarning.classList.add('d-none');
                    btnConnectQz.innerHTML = '<i class="fa-solid fa-check me-1"></i> Terhubung';
                    btnConnectQz.className = 'btn btn-outline-success btn-sm';
                    btnReloadPrinters.disabled = false;
                } else {
                    qzStatusDot.className = 'status-dot disconnected';
                    qzStatusText.textContent = 'QZ Tray: Tidak Terhubung';
                    qzStatusText.className = 'small fw-semibold text-danger';
                    qzAlertWarning.classList.remove('d-none');
                    btnConnectQz.innerHTML = '<i class="fa-solid fa-plug me-1"></i> Sambungkan QZ Tray';
                    btnConnectQz.className = 'btn btn-outline-primary btn-sm';
                    btnReloadPrinters.disabled = true;
                }
            }

            // 3. Ambil daftar printer yang tersedia dari QZ Tray
            async function populatePrinters() {
                try {
                    const printers = await qz.printers.find();
                    printerSelect.innerHTML = '<option value="">-- Pilih Printer Terpasang --</option>';
                    const savedPrinter = localStorage.getItem(SELECTED_PRINTER_KEY);

                    let autoSelected = false;
                    printers.forEach(printer => {
                        const opt = document.createElement('option');
                        opt.value = printer;
                        opt.textContent = printer;
                        // Pilih printer yang mengandung kata PLQ / Epson / Generic / tersimpan sebelumnya
                        if (savedPrinter && savedPrinter === printer) {
                            opt.selected = true;
                            autoSelected = true;
                        } else if (!autoSelected && /plq|epson.*pr2|generic.*text/i.test(printer)) {
                            opt.selected = true;
                            autoSelected = true;
                        }
                        printerSelect.appendChild(opt);
                    });

                    // Jangan lakukan fallback otomatis ke printerSelect.selectedIndex = 1
                    // Jika printer passbook tidak terdeteksi otomatis, wajibkan pengguna memilih secara sadar.
                } catch (err) {
                    console.error('Gagal mengambil daftar printer:', err);
                    printerSelect.innerHTML = '<option value="">-- Gagal memuat printer dari QZ Tray --</option>';
                }
            }

            printerSelect.addEventListener('change', () => {
                if (printerSelect.value) {
                    localStorage.setItem(SELECTED_PRINTER_KEY, printerSelect.value);
                }
            });

            btnConnectQz.addEventListener('click', () => connectQzTray(true));
            btnReloadPrinters.addEventListener('click', async () => {
                await populatePrinters();
                alert('Daftar printer telah diperbarui.');
            });
            btnTestPingQz.addEventListener('click', () => {
                if (isQzConnected) {
                    alert('Koneksi QZ Tray aktif dan siap mencetak!');
                } else {
                    connectQzTray(true);
                }
            });

            // 4. Kumpulkan Form Data menjadi JSON Payload Object
            function gatherFormData(overrideMode = 'dummy') {
                const formData = new FormData(form);
                const payload = {
                    mode: overrideMode,
                    baris_mulai: parseInt(formData.get('baris_mulai') || 0, 10),
                    jumlah_baris: parseInt(formData.get('jumlah_baris') || 1, 10),
                    seed: formData.get('seed') ? parseInt(formData.get('seed'), 10) : null,
                    margin_atas: parseFloat(formData.get('margin_atas') || 20),
                    margin_bawah: parseFloat(formData.get('margin_bawah') || 15),
                    margin_kiri: parseFloat(formData.get('margin_kiri') || 6),
                    margin_kanan: parseFloat(formData.get('margin_kanan') || 6),
                    tinggi_baris: parseFloat(formData.get('tinggi_baris') || 4.23),
                    lebar_halaman: parseFloat(formData.get('lebar_halaman') || 150),
                    tinggi_halaman: parseFloat(formData.get('tinggi_halaman') || 210),
                    pitch: parseInt(formData.get('pitch') || 10, 10),
                    font: formData.get('font') || '003',
                    unit_vertikal: parseInt(formData.get('unit_vertikal') || 240, 10),
                    referensi_vertikal: formData.get('referensi_vertikal') || 'edge',
                    tipe_jilid: formData.get('tipe_jilid') || 'horizontal',
                    mode_keluar: formData.get('mode_keluar') || 'front',
                    columns: []
                };

                for (let i = 0; i < 7; i++) {
                    const name = formData.get(`columns[${i}][name]`) || '';
                    const x = parseFloat(formData.get(`columns[${i}][x]`) || 0);
                    const width = parseFloat(formData.get(`columns[${i}][width]`) || 10);
                    const align = formData.get(`columns[${i}][align]`) || 'left';
                    payload.columns.push({ name, x, width, align });
                }

                return payload;
            }

            // 5. Kirim Request AJAX ke Backend untuk generate payload PR2 & SVG
            async function requestPayload(mode = 'dummy') {
                const payloadData = gatherFormData(mode);

                try {
                    const response = await fetch('{{ route("test-cetak.payload") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify(payloadData)
                    });

                    const json = await response.json();
                    if (response.status === 200 && json.status === 'success') {
                        currentPayloadBase64 = json.data;
                        currentHexDump = json.hex_preview;
                        currentGridInfo = json.grid_info;
                        currentWarnings = json.peringatan || [];

                        renderHexDump(json.hex_preview);
                        renderWarnings(json.peringatan);
                        renderSvgPreview(json.grid_info);
                        saveToLocalStorage();
                        return json;
                    } else {
                        let errMsg = 'Gagal memproses payload printer:';
                        if (json.errors) {
                            for (const k in json.errors) {
                                errMsg += `\n• ${json.errors[k].join(', ')}`;
                            }
                        } else if (json.message) {
                            errMsg += `\n${json.message}`;
                        }
                        alert(errMsg);
                        return null;
                    }
                } catch (err) {
                    console.error('Request error:', err);
                    alert('Terjadi kesalahan jaringan saat meminta payload ke server.');
                    return null;
                }
            }

            // 6. Render Hex Dump Viewer
            function renderHexDump(hexString) {
                hexdumpCode.textContent = hexString || '(Tidak ada data)';
                if (currentPayloadBase64) {
                    const byteLen = Math.round((currentPayloadBase64.length * 3) / 4);
                    hexBytesCount.textContent = `${byteLen} bytes`;
                } else {
                    hexBytesCount.textContent = '0 bytes';
                }
            }

            // 7. Render Warnings Box
            function renderWarnings(warnings) {
                if (warnings && warnings.length > 0) {
                    listWarnings.innerHTML = '';
                    warnings.forEach(w => {
                        const li = document.createElement('li');
                        li.textContent = w;
                        listWarnings.appendChild(li);
                    });
                    boxWarnings.classList.remove('d-none');
                } else {
                    boxWarnings.classList.add('d-none');
                    listWarnings.innerHTML = '';
                }
            }

            // 8. Render Pratinjau SVG Visual Skala Milimeter
            function renderSvgPreview(grid) {
                if (!grid) return;

                const pw = grid.lebar_halaman;
                const ph = grid.tinggi_halaman;
                const mTop = grid.margin_atas;
                const mBottom = grid.margin_bawah;
                const mLeft = grid.margin_kiri;
                const mRight = grid.margin_kanan;
                const printW = grid.printable_width_mm;
                const printH = grid.printable_height_mm;
                const charW = grid.char_width_mm;

                previewGridMeta.textContent = `${pw} × ${ph} mm | Area Cetak: ${printW} × ${printH} mm (${grid.max_chars} chars @ ${grid.pitch} CPI)`;

                // Margin visual tambahan (padding SVG)
                const pad = 10;
                const svgW = pw + (pad * 2);
                const svgH = ph + (pad * 2);

                svgPreview.setAttribute('viewBox', `0 0 ${svgW} ${svgH}`);
                svgPreview.innerHTML = '';

                // Defs untuk pattern garis grid
                const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                defs.innerHTML = `
                    <pattern id="grid-pattern" width="${charW}" height="${grid.tinggi_baris}" patternUnits="userSpaceOnUse">
                        <line x1="0" y1="0" x2="0" y2="${grid.tinggi_baris}" stroke="#f1f5f9" stroke-width="0.3"/>
                        <line x1="0" y1="0" x2="${charW}" y2="0" stroke="#f1f5f9" stroke-width="0.3"/>
                    </pattern>
                `;
                svgPreview.appendChild(defs);

                // Lembar Kertas Utama (Background Putih)
                const paper = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                paper.setAttribute('x', pad);
                paper.setAttribute('y', pad);
                paper.setAttribute('width', pw);
                paper.setAttribute('height', ph);
                paper.setAttribute('fill', '#ffffff');
                paper.setAttribute('stroke', '#94a3b8');
                paper.setAttribute('stroke-width', '0.8');
                paper.setAttribute('rx', '2');
                svgPreview.appendChild(paper);

                // Shading Margin Abu-abu
                const createShade = (x, y, w, h) => {
                    if (w <= 0 || h <= 0) return;
                    const r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    r.setAttribute('x', pad + x);
                    r.setAttribute('y', pad + y);
                    r.setAttribute('width', w);
                    r.setAttribute('height', h);
                    r.setAttribute('fill', '#e2e8f0');
                    r.setAttribute('opacity', '0.65');
                    svgPreview.appendChild(r);
                };

                // Margin Top
                createShade(0, 0, pw, mTop);
                // Margin Bottom
                createShade(0, ph - mBottom, pw, mBottom);
                // Margin Left
                createShade(0, mTop, mLeft, ph - mTop - mBottom);
                // Margin Right
                createShade(pw - mRight, mTop, mRight, ph - mTop - mBottom);

                // Garis Batas Area Cetak (Dotted Blue)
                const printArea = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                printArea.setAttribute('x', pad + mLeft);
                printArea.setAttribute('y', pad + mTop);
                printArea.setAttribute('width', printW);
                printArea.setAttribute('height', printH);
                printArea.setAttribute('fill', 'url(#grid-pattern)');
                printArea.setAttribute('stroke', '#38bdf8');
                printArea.setAttribute('stroke-width', '0.6');
                printArea.setAttribute('stroke-dasharray', '2,2');
                svgPreview.appendChild(printArea);

                // Kolom Panduan Vertikal
                if (grid.columns) {
                    grid.columns.forEach((col, idx) => {
                        const colX = pad + mLeft + col.x_mm;
                        const colW = col.width_mm;
                        const colRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        colRect.setAttribute('x', colX);
                        colRect.setAttribute('y', pad + mTop);
                        colRect.setAttribute('width', colW);
                        colRect.setAttribute('height', printH);
                        colRect.setAttribute('fill', idx % 2 === 0 ? 'rgba(56, 189, 248, 0.04)' : 'rgba(99, 102, 241, 0.04)');
                        colRect.setAttribute('stroke', '#cbd5e1');
                        colRect.setAttribute('stroke-width', '0.4');

                        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                        title.textContent = `Kolom: ${col.name}\nX: ${col.x_mm}mm (char ${col.char_pos})\nLebar: ${col.width_mm}mm (${col.width_chars} chars)`;
                        colRect.appendChild(title);
                        svgPreview.appendChild(colRect);

                        // Header Label Kolom di Atas
                        const colLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        colLabel.setAttribute('x', colX + 1);
                        colLabel.setAttribute('y', pad + Math.max(3, mTop - 2));
                        colLabel.setAttribute('font-size', '3.5');
                        colLabel.setAttribute('font-family', 'sans-serif');
                        colLabel.setAttribute('font-weight', 'bold');
                        colLabel.setAttribute('fill', '#475569');
                        colLabel.textContent = col.name;
                        svgPreview.appendChild(colLabel);
                    });
                }

                // Render Garis Baris Aktif
                if (grid.lines) {
                    grid.lines.forEach((line) => {
                        const lineY = pad + line.y_mm;
                        const isOutOfBounds = !line.is_printed;

                        const rowLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        rowLine.setAttribute('x1', pad + mLeft);
                        rowLine.setAttribute('y1', lineY);
                        rowLine.setAttribute('x2', pad + pw - mRight);
                        rowLine.setAttribute('y2', lineY);
                        rowLine.setAttribute('stroke', isOutOfBounds ? '#ef4444' : '#22c55e');
                        rowLine.setAttribute('stroke-width', '0.8');
                        if (isOutOfBounds) {
                            rowLine.setAttribute('stroke-dasharray', '2,1');
                        }

                        const lineTitle = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                        lineTitle.textContent = `Baris ${line.line_display} (Y = ${line.y_mm} mm)${isOutOfBounds ? ' [MELEWATI BATAS BAWAH]' : ''}`;
                        rowLine.appendChild(lineTitle);
                        svgPreview.appendChild(rowLine);

                        // Label Baris di Margin Kiri
                        const rowText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        rowText.setAttribute('x', pad + Math.max(1, mLeft - 5));
                        rowText.setAttribute('y', lineY + 1.2);
                        rowText.setAttribute('font-size', '3.5');
                        rowText.setAttribute('font-family', 'monospace');
                        rowText.setAttribute('fill', isOutOfBounds ? '#ef4444' : '#15803d');
                        rowText.textContent = `${line.line_display}`;
                        svgPreview.appendChild(rowText);

                        // Pratinjau teks baris di area cetak jika muat
                        if (!isOutOfBounds && line.data) {
                            grid.columns.forEach(col => {
                                const val = line.data[col.name];
                                if (val) {
                                    const colText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                                    const isRight = col.align === 'right' || ['DEBET', 'KREDIT', 'SALDO'].includes(col.name);
                                    const textX = isRight
                                        ? pad + mLeft + col.x_mm + col.width_mm - 1
                                        : pad + mLeft + col.x_mm + 0.5;

                                    colText.setAttribute('x', textX);
                                    colText.setAttribute('y', lineY - 0.8);
                                    colText.setAttribute('font-size', '2.8');
                                    colText.setAttribute('font-family', 'monospace');
                                    colText.setAttribute('fill', '#0f172a');
                                    if (isRight) {
                                        colText.setAttribute('text-anchor', 'end');
                                    }
                                    colText.textContent = val;
                                    svgPreview.appendChild(colText);
                                }
                            });
                        }
                    });
                }
            }

            // 9. Simpan & Pulihkan Konfigurasi Form dari LocalStorage
            function saveToLocalStorage() {
                const data = gatherFormData();
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

            function loadFromLocalStorage() {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (!saved) return;
                try {
                    const data = JSON.parse(saved);
                    for (const key in data) {
                        if (key === 'columns' && Array.isArray(data.columns)) {
                            data.columns.forEach((col, idx) => {
                                const xInput = document.getElementById(`col_${idx}_x`);
                                const wInput = document.getElementById(`col_${idx}_width`);
                                if (xInput) xInput.value = col.x;
                                if (wInput) wInput.value = col.width;
                            });
                        } else {
                            const input = document.getElementById(key);
                            if (input) input.value = data[key];
                        }
                    }
                } catch (e) {
                    console.error('Gagal memulihkan dari localStorage:', e);
                }
            }

            function resetToDefaults() {
                if (!confirm('Apakah Anda yakin ingin mengembalikan seluruh parameter margin dan kolom ke standar pabrik?')) {
                    return;
                }
                localStorage.removeItem(STORAGE_KEY);
                for (const key in defaultValues) {
                    if (key === 'columns' && Array.isArray(defaultValues.columns)) {
                        defaultValues.columns.forEach((col, idx) => {
                            const xInput = document.getElementById(`col_${idx}_x`);
                            const wInput = document.getElementById(`col_${idx}_width`);
                            if (xInput) xInput.value = col.x;
                            if (wInput) wInput.value = col.width;
                        });
                    } else {
                        const input = document.getElementById(key);
                        if (input) input.value = defaultValues[key];
                    }
                }
                requestPayload('dummy');
            }

            // 10. Cetak Langsung ke QZ Tray
            async function executePrintJob(mode = 'dummy') {
                const selectedPrinter = printerSelect.value;
                if (!selectedPrinter) {
                    alert('PERHATIAN: Silakan pilih printer fisik PLQ-20 atau Generic/Text Only pada dropdown terlebih dahulu!\n\nJangan mencetak ke printer virtual (Microsoft Print to PDF / OneNote) karena antrean fisik tidak akan berjalan.');
                    if (!isQzConnected) {
                        await connectQzTray(true);
                    }
                    printerSelect.focus();
                    return;
                }

                // Ambil payload terkini
                const res = await requestPayload(mode);
                if (!res || !res.data) return;

                if (res.peringatan && res.peringatan.length > 0) {
                    const confirmPrint = confirm(
                        `PERINGATAN:\nTerdapat ${res.peringatan.length} peringatan batas cetak (baris/kolom terpotong):\n\n` +
                        res.peringatan.slice(0, 3).join('\n') +
                        (res.peringatan.length > 3 ? '\n...dan lainnya.' : '') +
                        '\n\nLanjutkan mengirim perintah cetak ke printer?'
                    );
                    if (!confirmPrint) return;
                }

                const byteSize = res.data ? Math.round(res.data.length * 0.75) : 0;
                const confirmPrintJob = confirm(
                    `KIRIM PERINTAH CETAK:\n` +
                    `- Target Printer: ${selectedPrinter}\n` +
                    `- Mode: ${mode === 'calibration' ? 'Kalibrasi Titik & Grid' : 'Data Dummy Tabungan'}\n` +
                    `- Ukuran Payload RAW: ~${byteSize} byte\n\n` +
                    `Pastikan buku tabungan sudah dimasukkan ke slot depan hingga ditarik printer dan lampu Ready menyala.\n\n` +
                    `Lanjutkan mengirim ke printer?`
                );
                if (!confirmPrintJob) return;

                try {
                    // Pastikan QZ Tray terhubung
                    if (!qz.websocket.isActive()) {
                        await qz.websocket.connect();
                        setQzStatus(true);
                    }

                    const config = qz.configs.create(selectedPrinter, {
                        encoding: 'ISO-8859-1'
                    });

                    const printData = [{
                        type: 'raw',
                        format: 'command',
                        flavor: 'base64',
                        data: res.data
                    }];

                    await qz.print(config, printData);
                    alert(
                        `Perintah cetak (${mode === 'calibration' ? 'Kalibrasi' : 'Data Dummy'}) berhasil dikirim ke printer!\n\n` +
                        `Target Printer: ${selectedPrinter}\n` +
                        `Ukuran Data: ~${byteSize} byte\n\n` +
                        `Catatan Penting:\n` +
                        `Jika antrean Windows langsung kosong dan printer tidak bereaksi, periksa:\n` +
                        `1. Uji "Pause Printing" di Windows Spooler untuk melihat dokumen tertahan.\n` +
                        `2. Pastikan menggunakan driver "Generic / Text Only" (bukan driver grafis Epson).\n` +
                        `3. Pastikan port USB yang dipilih sesuai (misal USB001).\n` +
                        `4. Pastikan emulasi hardware diset ke PR2 (F1+F2 saat dinyalakan).`
                    );
                } catch (err) {
                    console.error('Error saat mencetak via QZ Tray:', err);
                    alert(`Gagal mencetak ke ${selectedPrinter}:\n${err.message || err}`);
                }
            }

            // 11. Unduh Payload sebagai File Biner (.bin)
            function downloadBinaryPayload() {
                if (!currentPayloadBase64) {
                    alert('Payload belum tersedia. Silakan klik tombol "Perbarui Pratinjau".');
                    return;
                }

                try {
                    const binaryString = atob(currentPayloadBase64);
                    const bytes = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }

                    const blob = new Blob([bytes], { type: 'application/octet-stream' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `passbook-plq20-pr2-${Date.now()}.bin`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (e) {
                    console.error('Gagal membuat unduhan biner:', e);
                    alert('Gagal mengonversi payload base64 ke file biner.');
                }
            }

            // 12. Salin Hex Dump ke Clipboard
            btnCopyHex.addEventListener('click', async () => {
                if (!currentHexDump) return;
                try {
                    await navigator.clipboard.writeText(currentHexDump);
                    const originalText = btnCopyHex.innerHTML;
                    btnCopyHex.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i> Tersalin!';
                    setTimeout(() => { btnCopyHex.innerHTML = originalText; }, 2000);
                } catch (e) {
                    alert('Gagal menyalin hex dump ke papan klip.');
                }
            });

            // 13. Event Listeners untuk Form Changes (Debounced Auto Update)
            form.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    requestPayload('dummy');
                }, 400);
            });

            form.addEventListener('change', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    requestPayload('dummy');
                }, 200);
            });

            // Action Buttons
            btnPrintDummy.addEventListener('click', () => executePrintJob('dummy'));
            btnPrintCalibration.addEventListener('click', () => executePrintJob('calibration'));
            btnDownloadBin.addEventListener('click', downloadBinaryPayload);
            btnResetDefaults.addEventListener('click', resetToDefaults);
            btnRefreshPreview.addEventListener('click', () => requestPayload('dummy'));

            // Inisialisasi awal saat halaman dimuat
            document.addEventListener('DOMContentLoaded', async () => {
                loadFromLocalStorage();
                await requestPayload('dummy');
                // Coba hubungkan secara pasif ke QZ Tray di latar belakang
                setTimeout(() => {
                    connectQzTray(false);
                }, 500);
            });

        })();
    </script>
</body>
</html>
