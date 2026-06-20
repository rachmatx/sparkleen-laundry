<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($transaksi) ? 'Lacak Nota ' . esc($transaksi['no_nota']) : 'Lacak Order' ?> - Sparkleen Laundry</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('/favicon.png') ?>">
    
    <!-- Google Font: Inter (Premium Modern Font) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" crossorigin="anonymous">
    
    <style>
        html, body {
            overflow-x: hidden;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            padding: 40px 10px;
        }

        /* Dekorasi latar belakang (Glassmorphism circle) */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(13,110,253,0.08), rgba(13,110,253,0.01));
            z-index: 0;
        }
        .bg-circle-1 { width: 400px; height: 400px; top: -100px; left: -100px; }
        .bg-circle-2 { width: 600px; height: 600px; bottom: -150px; right: -150px; }

        .lacak-wrapper {
            width: 100%;
            max-width: <?= $transaksi ? '960px' : '520px' ?>;
            z-index: 1;
            transition: max-width 0.4s ease-in-out;
        }

        .lacak-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .lacak-header {
            text-align: center;
            padding: 35px 30px 20px;
        }

        .lacak-logo {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .lacak-logo span {
            color: #0d6efd;
        }
        
        .lacak-subtitle {
            font-size: 14px;
            color: #6c757d;
            font-weight: 400;
        }

        .lacak-body {
            padding: 10px 30px 35px;
        }

        .search-container {
            max-width: 650px;
            margin: 0 auto;
        }

        .search-group-modern {
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .search-group-modern:focus-within {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .search-group-modern .input-group-text {
            background-color: #fff;
            border: none;
            color: #6c757d;
            padding-left: 20px;
        }

        .search-group-modern .form-control {
            border: none;
            padding: 12px 15px;
            font-weight: 500;
            background-color: #fff;
            box-shadow: none;
        }

        .search-group-modern .btn-lacak {
            padding: 12px 28px;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            color: #fff;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        .search-group-modern .btn-lacak:hover {
            background: linear-gradient(135deg, #0b5ed7, #094eb8);
        }

        /* Notifikasi */
        .alert {
            border-radius: 12px;
            border: none;
            font-size: 14px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }
        .alert-danger { background-color: #ffe6e6; color: #cc0000; }
        .alert i { margin-right: 10px; font-size: 16px; }

        /* Stepper Styles */
        .stepper-outer {
            padding: 20px 0 10px;
            margin-bottom: 25px;
        }
        
        .stepper-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            /* Variabel CSS untuk kemajuan persentase progress bar */
            --progress-percent: 0%;
        }

        .stepper-progress-bar {
            position: absolute;
            top: 22px;
            left: 0;
            right: 0;
            height: 6px;
            background-color: #e9ecef;
            z-index: 1;
            border-radius: 3px;
            margin: 0 45px;
        }

        .stepper-progress-fill {
            width: var(--progress-percent);
            height: 100%;
            background: linear-gradient(90deg, #0d6efd, #198754);
            border-radius: 3px;
            transition: width 0.6s ease-in-out;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
        }

        .step-node {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 90px;
        }

        .step-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background-color: #fff;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #6c757d;
            transition: all 0.4s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.04);
        }

        .step-node.completed .step-circle {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }

        .step-node.active .step-circle {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
            box-shadow: 0 0 15px rgba(13, 110, 253, 0.4);
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }

        .step-label {
            margin-top: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .step-node.active .step-label {
            color: #0d6efd;
        }

        .step-node.completed .step-label {
            color: #198754;
        }

        /* Detail Boxes */
        .info-card-summary {
            background: rgba(13, 110, 253, 0.03);
            border: 1px solid rgba(13, 110, 253, 0.08);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .status-highlight-card {
            background: #fff;
            border-left: 5px solid #0d6efd;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            padding: 18px 20px;
            margin-bottom: 25px;
        }
        .status-highlight-card.status-selesai {
            border-left-color: #198754;
        }
        .status-highlight-card.status-diambil {
            border-left-color: #212529;
        }
        .status-highlight-card.status-proses {
            border-left-color: #0d6efd;
        }
        .status-highlight-card.status-setrika {
            border-left-color: #0dcaf0;
        }

        .badge-payment {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
        }
        .badge-payment-lunas { background-color: #e6f9eb; color: #198754; }
        .badge-payment-cicil { background-color: #fff3cd; color: #856404; }
        .badge-payment-belum_lunas { background-color: #f8d7da; color: #721c24; }

        .detail-table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        .log-timeline {
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #dee2e6;
            margin-left: 10px;
        }
        .log-timeline-item {
            position: relative;
            margin-bottom: 15px;
        }
        .log-timeline-item:last-child {
            margin-bottom: 0;
        }
        .log-timeline-dot {
            position: absolute;
            left: -27px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
            border: 2px solid #fff;
        }
        .log-timeline-item:first-child .log-timeline-dot {
            background-color: #198754;
            transform: scale(1.2);
            box-shadow: 0 0 5px rgba(25, 135, 84, 0.5);
        }

        /* Responsive Mobile Layout */
        @media (max-width: 575.98px) {
            .stepper-container {
                flex-direction: column;
                align-items: flex-start;
                padding-left: 20px;
                margin: 20px 0;
            }
            .stepper-progress-bar {
                left: 40px;
                top: 0;
                bottom: 0;
                width: 6px;
                height: calc(100% - 46px);
                margin: 23px 0;
            }
            .stepper-progress-fill {
                width: 100% !important;
                height: var(--progress-percent);
                transition: height 0.6s ease-in-out;
            }
            .step-node {
                flex-direction: row;
                text-align: left;
                width: 100%;
                margin-bottom: 25px;
            }
            .step-node:last-child {
                margin-bottom: 0;
            }
            .step-label {
                margin-top: 0;
                margin-left: 15px;
            }
            .lacak-body {
                padding: 10px 20px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <div class="lacak-wrapper">
        <div class="lacak-card">
            <!-- Header Logo -->
            <div class="lacak-header">
                <a href="<?= base_url('/lacak') ?>" class="lacak-logo"><span>Sparkleen</span> Laundry</a>
                <div class="lacak-subtitle">Lacak status proses cucian Anda secara langsung</div>
            </div>
            
            <div class="lacak-body">
                <!-- Search Tabs -->
                <div class="search-container mb-4">
                    <?php $activeTab = ($telp !== '' && $notaInput === null) ? 'telp' : 'nota'; ?>
                    <ul class="nav nav-pills mb-3 justify-content-center gap-2" id="searchTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $activeTab === 'nota' ? 'active' : '' ?>"
                               id="tab-nota"
                               href="#"
                               onclick="switchTab('nota'); return false;"
                               style="border-radius: 8px; font-weight: 600; font-size: 14px; padding: 8px 20px;">
                                <i class="fas fa-receipt me-2"></i>Nomor Nota
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $activeTab === 'telp' ? 'active' : '' ?>"
                               id="tab-telp"
                               href="#"
                               onclick="switchTab('telp'); return false;"
                               style="border-radius: 8px; font-weight: 600; font-size: 14px; padding: 8px 20px;">
                                <i class="fas fa-mobile-alt me-2"></i>Nomor HP
                            </a>
                        </li>
                    </ul>

                    <!-- Form: Cari by Nota -->
                    <div id="form-nota" style="display: <?= $activeTab === 'nota' ? 'block' : 'none' ?>;">
                        <form action="<?= base_url('lacak') ?>" method="get">
                            <div class="input-group search-group-modern">
                                <span class="input-group-text">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <input type="text"
                                       class="form-control"
                                       name="nota"
                                       value="<?= esc($notaInput ?? '') ?>"
                                       placeholder="Contoh: LAU/20260522/0001"
                                       autocomplete="off">
                                <button type="submit" class="btn btn-lacak">
                                    Lacak Order
                                </button>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted">Masukkan nomor nota yang tertera di struk pembayaran Anda</small>
                            </div>
                        </form>
                    </div>

                    <!-- Form: Cari by Nomor HP -->
                    <div id="form-telp" style="display: <?= $activeTab === 'telp' ? 'block' : 'none' ?>;">
                        <form action="<?= base_url('lacak') ?>" method="get">
                            <div class="input-group search-group-modern">
                                <span class="input-group-text">
                                    <i class="fas fa-mobile-alt"></i>
                                </span>
                                <input type="text"
                                       class="form-control"
                                       name="telp"
                                       value="<?= esc($telp ?? '') ?>"
                                       placeholder="Contoh: 081234567890 atau 6281234567890"
                                       autocomplete="off"
                                       inputmode="numeric">
                                <button type="submit" class="btn btn-lacak">
                                    Cari Order
                                </button>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted">Masukkan nomor HP yang terdaftar saat transaksi — akan menampilkan order terbaru Anda</small>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                function switchTab(tab) {
                    document.getElementById('form-nota').style.display = tab === 'nota' ? 'block' : 'none';
                    document.getElementById('form-telp').style.display = tab === 'telp' ? 'block' : 'none';
                    document.getElementById('tab-nota').classList.toggle('active', tab === 'nota');
                    document.getElementById('tab-telp').classList.toggle('active', tab === 'telp');
                }
                </script>


                <!-- Error Messages -->
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= esc($errorMsg) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Info Banner: Multiple Nota Aktif -->
                <?php if (!empty($transaksi['has_other_nota'])): ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4" role="alert" style="background: linear-gradient(135deg, #cff4fc 0%, #e6f7fb 100%);">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-3 mt-1 fs-5 text-info"></i>
                            <div class="flex-grow-1">
                                <strong>Terdapat <?= (int)$transaksi['total_nota_aktif'] ?> cucian aktif</strong>
                                untuk nomor HP ini.
                                <div class="small text-muted mt-1 mb-2">
                                    <?php if ($transaksi['total_nota_aktif'] > 5): ?>
                                        Menampilkan <strong>5 nota terbaru</strong>. Untuk cek sisa nota lainnya,
                                        buka <strong>pesan WhatsApp</strong> dari kami.
                                    <?php else: ?>
                                        Menampilkan nota terbaru. Untuk cek nota lainnya,
                                        buka <strong>pesan WhatsApp</strong> dari kami (nomor nota tercantum di dalam pesan)
                                        atau pilih dari daftar di bawah:
                                    <?php endif; ?>
                                </div>
                                <div class="list-group list-group-flush bg-transparent">
                                    <?php foreach (($daftarNotaAktif ?? []) as $n): ?>
                                        <a href="<?= base_url('lacak?nota=' . $n['no_nota']) ?>"
                                           class="list-group-item list-group-item-action bg-transparent border-0 ps-0 py-1 small <?= ($n['no_nota'] === $transaksi['no_nota']) ? 'fw-bold text-primary' : 'text-info' ?>">
                                            <?php if ($n['no_nota'] === $transaksi['no_nota']): ?>
                                                <i class="fas fa-arrow-right me-2"></i>
                                            <?php else: ?>
                                                <i class="fas fa-chevron-right me-2"></i>
                                            <?php endif; ?>
                                            <?= esc($n['no_nota']) ?>
                                            <span class="badge bg-secondary ms-2"><?= esc($n['status']) ?></span>
                                            <span class="text-muted ms-2">· <?= date('d/m/Y', strtotime($n['tgl_masuk'])) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tracking Result -->
                <?php if ($transaksi): ?>
                    <hr class="my-4 opacity-50">

                    <?php
                        // Menentukan persentase progres dan langkah aktif
                        $currentStatus = $transaksi['status'];
                        $timelineItem  = $statusTimeline[$currentStatus] ?? $statusTimeline['menunggu'];
                        $activeStep    = $timelineItem['step'];
                        $progressPct   = $timelineItem['percent'];
                    ?>

                    <!-- Stepper Progress Tracker -->
                    <div class="stepper-outer">
                        <h5 class="fw-bold mb-3"><i class="fas fa-route text-primary me-2"></i>Status Kemajuan</h5>
                        <div class="stepper-container" style="--progress-percent: <?= $progressPct ?>%;">
                            <div class="stepper-progress-bar">
                                <div class="stepper-progress-fill"></div>
                            </div>
                            
                            <?php foreach ($statusTimeline as $statusKey => $stepData): 
                                $class = '';
                                if ($stepData['step'] < $activeStep) {
                                    $class = 'completed';
                                } elseif ($stepData['step'] == $activeStep) {
                                    $class = 'active';
                                }
                            ?>
                                <div class="step-node <?= $class ?>">
                                    <div class="step-circle" title="<?= esc($stepData['label']) ?>">
                                        <i class="fas <?= esc($stepData['icon']) ?>"></i>
                                    </div>
                                    <div class="step-label"><?= esc($stepData['label']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Highlight Status Saat Ini -->
                    <div class="status-highlight-card status-<?= esc($currentStatus) ?>">
                        <div class="d-flex align-items-center">
                            <div class="fs-1 me-3 text-<?= $currentStatus === 'selesai' ? 'success' : ($currentStatus === 'diambil' ? 'dark' : ($currentStatus === 'setrika' ? 'info' : ($currentStatus === 'proses' ? 'primary' : 'secondary'))) ?>">
                                <i class="fas <?= esc($timelineItem['icon']) ?>"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">STATUS TERKINI</div>
                                <h5 class="fw-bold mb-1" style="color: <?= esc($timelineItem['color']) ?>;"><?= esc($timelineItem['label']) ?></h5>
                                <p class="mb-0 text-muted small"><?= esc($timelineItem['desc']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Transaksi -->
                    <div class="info-card-summary">
                        <div class="row g-3">
                            <div class="col-6 col-sm-3">
                                <small class="text-muted d-block">Nomor Nota</small>
                                <span class="fw-bold text-dark"><?= esc($transaksi['no_nota']) ?></span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <small class="text-muted d-block">Pembayaran</small>
                                <?php 
                                    $payStatus = strtolower($transaksi['status_pembayaran']);
                                    $payLabel = strtoupper($transaksi['status_pembayaran']);
                                    $payClass = 'badge-payment-' . $payStatus;
                                ?>
                                <span class="badge-payment <?= $payClass ?>"><?= esc($payLabel) ?></span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <small class="text-muted d-block">Tanggal Masuk</small>
                                <span class="fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($transaksi['tgl_masuk'])) ?></span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <small class="text-muted d-block">Estimasi Selesai</small>
                                <span class="fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_estimasi'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Cucian & Biaya -->
                    <div class="row g-4">
                        <div class="col-md-6 col-12">
                            <div class="card border border-light h-100 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-user-shield me-2"></i>Verifikasi Pelanggan</h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted" width="40%">Nama Pelanggan</td>
                                                <td class="fw-bold"><?= esc($transaksi['nama_pelanggan_masked']) ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">No. Telepon</td>
                                                <td class="fw-bold"><?= esc($transaksi['no_telp_masked']) ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Total Berat</td>
                                                <td class="fw-bold"><?= $transaksi['total_berat'] ?> kg</td>
                                            </tr>
                                            <?php if ($transaksi['tgl_selesai_aktual']): ?>
                                                <tr>
                                                    <td class="text-muted">Selesai Aktual</td>
                                                    <td class="fw-bold text-success"><?= date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_aktual'])) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="card border border-light h-100 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-list-ol me-2"></i>Rincian Layanan</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Layanan</th>
                                                    <th class="text-center">Berat/Qty</th>
                                                    <th class="text-end">Harga</th>
                                                    <th class="text-end">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($detail as $d): ?>
                                                    <tr>
                                                        <td><?= esc($d['nama_layanan']) ?></td>
                                                        <td class="text-center"><?= esc($d['berat']) ?> kg</td>
                                                        <td class="text-end">Rp<?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
                                                        <td class="text-end">Rp<?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="border-top border-2">
                                                <?php if ($transaksi['diskon'] > 0 || $transaksi['poin_ditebus'] > 0): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-muted small">Total Biaya</td>
                                                        <td class="text-end small">Rp<?= number_format($transaksi['total_biaya'], 0, ',', '.') ?></td>
                                                    </tr>
                                                    <?php if ($transaksi['diskon'] > 0): ?>
                                                        <tr>
                                                            <td colspan="3" class="text-end text-success small">Diskon</td>
                                                            <td class="text-end text-success small">-Rp<?= number_format($transaksi['diskon'], 0, ',', '.') ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php if ($transaksi['poin_ditebus'] > 0): ?>
                                                        <tr>
                                                            <td colspan="3" class="text-end text-success small">Tukar Poin</td>
                                                            <td class="text-end text-success small">-Rp<?= number_format($transaksi['poin_ditebus'], 0, ',', '.') ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <tr>
                                                    <th colspan="3" class="text-end text-dark">Grand Total</th>
                                                    <th class="text-end text-primary fs-6">Rp<?= number_format($transaksi['grand_total'], 0, ',', '.') ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Detail Status -->
                    <?php if (!empty($statusLog)): ?>
                        <div class="mt-4">
                            <div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="accordionHistory">
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold text-secondary bg-white" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapseHistory" 
                                                aria-expanded="false" 
                                                aria-controls="collapseHistory">
                                            <i class="fas fa-history me-2 text-primary"></i> Lihat Riwayat Pelacakan Lengkap
                                        </button>
                                    </h2>
                                    <div id="collapseHistory" class="accordion-collapse collapse" data-bs-parent="#accordionHistory">
                                        <div class="accordion-body bg-white pt-2">
                                            <div class="log-timeline mt-2">
                                                <?php foreach ($statusLog as $log): 
                                                    // Map status baru ke label pelanggan
                                                    $logStatusKey = strtolower($log['status_baru']);
                                                    $logLabel = $statusTimeline[$logStatusKey]['label'] ?? ucfirst($log['status_baru']);
                                                ?>
                                                    <div class="log-timeline-item">
                                                        <div class="log-timeline-dot"></div>
                                                        <div class="fw-bold text-dark small mb-0"><?= esc($logLabel) ?></div>
                                                        <div class="text-muted" style="font-size: 11px;">
                                                            <?= date('d M Y H:i', strtotime($log['tgl_ubah'])) ?>
                                                        </div>
                                                        <?php if (!empty($log['catatan'])): ?>
                                                            <div class="text-muted small mt-1 p-2 bg-light rounded-3" style="font-size: 11.5px;">
                                                                <i class="fas fa-sticky-note me-1 text-warning"></i> <?= esc($log['catatan']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($searched && !$errorMsg): ?>
                    <!-- Fallback if some edge case happens -->
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Nota tidak valid. Silakan coba lagi.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer copyright -->
        <div class="text-center mt-4 text-muted small" style="z-index: 1;">
            &copy; <?= date('Y') ?> Sparkleen Laundry. All rights reserved.
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
