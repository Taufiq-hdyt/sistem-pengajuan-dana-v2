<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../index.php");
    exit();
}

// --- LOGIKA FILTER PENCARIAN ---
$where_clause = "WHERE p.status_finance != 'Pending'";

// 1. Filter Bulan
$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : '';
if (!empty($filter_bulan)) {
    $bulan_aman = mysqli_real_escape_string($koneksi, $filter_bulan);
    $where_clause .= " AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$bulan_aman'";
}

// 2. Filter Status
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
if (!empty($filter_status)) {
    if ($filter_status === 'selesai') {
        $where_clause .= " AND p.status_laporan = 'Valid'";
    } elseif ($filter_status === 'ditolak') {
        $where_clause .= " AND p.status_keseluruhan = 'Ditolak'";
    } elseif ($filter_status === 'berjalan') {
        $where_clause .= " AND p.status_laporan != 'Valid' AND p.status_keseluruhan != 'Ditolak'";
    }
}

// --- LOGIKA PAGINATION (PEMBAGIAN HALAMAN) ---
$limit = 10; // Jumlah data per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_aktif - 1) * $limit;

// Hitung Total Data
$query_count = "SELECT COUNT(p.id) AS total_data FROM pengajuan p $where_clause";
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total_data'];
$total_halaman = ceil($total_data / $limit);

// AMBIL DATA RIWAYAT DENGAN LIMIT & OFFSET
$query_riwayat = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi 
                  FROM pengajuan p 
                  JOIN users u ON p.user_id = u.id 
                  LEFT JOIN divisi d ON u.divisi_id = d.id 
                  $where_clause 
                  ORDER BY p.tanggal_dibuat DESC 
                  LIMIT $limit OFFSET $offset";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);

// AMBIL DAFTAR BULAN UNTUK DROPDOWN
$query_dropdown_bulan = "SELECT DISTINCT DATE_FORMAT(tanggal_dibuat, '%Y-%m') AS val_bulan 
                         FROM pengajuan 
                         WHERE status_finance != 'Pending' 
                         ORDER BY val_bulan DESC";
$result_bulan = mysqli_query($koneksi, $query_dropdown_bulan);

$nama_bulan_indo = ['01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];

// Menjaga parameter filter saat pindah halaman
$parameter_url = "";
if(!empty($filter_bulan)) $parameter_url .= "&filter_bulan=$filter_bulan";
if(!empty($filter_status)) $parameter_url .= "&filter_status=$filter_status";

// Panggil Header
require 'header.php';
?>

<style>
    /* Styling Card Modern */
    .card-clean {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
        animation: slideUp 0.4s ease forwards;
    }

    .card-header-clean {
        background: #ffffff;
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Filter Box */
    .filter-wrapper {
        background: #f8fafc;
        padding: 15px 20px;
        border-radius: 12px;
        margin: 20px;
        border: 1px solid #e2e8f0;
    }

    .form-select-sm, .form-control-sm { border-radius: 8px; }

    /* Styling Tabel Modern */
    .table-modern { margin-bottom: 0; }
    .table-modern thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-modern tbody td {
        padding: 16px 20px;
        vertical-align: middle;
        font-size: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .table-modern tbody tr:hover { background-color: #f8fafc; }

    .vendor-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; display: block; margin-bottom: 3px; }
    .nominal-text { color: var(--lkpi-green); font-weight: 700; font-size: 1rem; }
    
    /* Smart Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 4px;
    }
    .badge-selesai { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-berjalan { background: #fef9c3; color: #b45309; border: 1px solid #fef08a; }
    .badge-ditolak { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-revisi { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .status-catatan {
        font-size: 0.7rem;
        line-height: 1.2;
        max-width: 220px;
        color: #64748b;
        display: block;
    }

    /* Tombol Audit */
    .btn-audit {
        background: #ffffff;
        color: #0ea5e9;
        border: 1px solid #bae6fd;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: 0.2s;
    }
    .btn-audit:hover { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }

    /* Pagination */
    .pagination { margin-bottom: 0; justify-content: flex-end; }
    .page-link { color: var(--text-main); border: none; font-size: 0.875rem; font-weight: 500; border-radius: 8px; margin: 0 2px; }
    .page-item.active .page-link { background-color: var(--lkpi-green); color: white; }
    .page-link:hover:not(.active) { background-color: #f1f5f9; color: var(--lkpi-green); }

    /* Modal Link Info */
    .link-berkas {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f1f5f9;
        color: var(--lkpi-green);
        padding: 4px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        transition: 0.2s;
        margin-right: 5px;
        margin-bottom: 5px;
    }
    .link-berkas:hover { background: #e2e8f0; color: #007031; }
    .link-tf { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
    .link-tf:hover { background: #bae6fd; color: #0369a1; }
</style>

<div class="card-clean">
    <div class="card-header-clean d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title fw-bold m-0"><i class="fas fa-server text-secondary me-2"></i> Pusat Riwayat & Audit</h5>
        <span class="text-muted" style="font-size: 0.8rem;">Menampilkan <b><?php echo ($total_data > 0) ? $offset + 1 : 0; ?> - <?php echo min($offset + $limit, $total_data); ?></b> dari <b><?php echo $total_data; ?></b> Transaksi</span>
    </div>

    <!-- Filter Box -->
    <div class="filter-wrapper">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-auto">
                <span class="text-muted fw-bold" style="font-size: 0.8rem;"><i class="fas fa-filter me-1"></i> Filter:</span>
            </div>
            <div class="col-12 col-md-3">
                <select name="filter_bulan" class="form-select form-select-sm">
                    <option value="">Semua Bulan</option>
                    <?php 
                    if(mysqli_num_rows($result_bulan) > 0) {
                        while($row_bln = mysqli_fetch_assoc($result_bulan)){
                            $val_bulan = $row_bln['val_bulan'];
                            $pecah = explode('-', $val_bulan);
                            $tahun = $pecah[0];
                            $bulan = $nama_bulan_indo[$pecah[1]];
                            
                            $label_tampil = $bulan . " " . $tahun;
                            $selected = ($filter_bulan == $val_bulan) ? 'selected' : '';
                            
                            echo "<option value='$val_bulan' $selected>$label_tampil</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <select name="filter_status" class="form-select form-select-sm">
                    <option value="">Semua Status Akhir</option>
                    <option value="selesai" <?php if($filter_status == 'selesai') echo 'selected'; ?>>✅ Selesai (Valid)</option>
                    <option value="berjalan" <?php if($filter_status == 'berjalan') echo 'selected'; ?>>⏳ Sedang Berjalan</option>
                    <option value="ditolak" <?php if($filter_status == 'ditolak') echo 'selected'; ?>>❌ Ditolak / Batal</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold">Terapkan</button>
                <a href="riwayat_audit.php" class="btn btn-sm btn-light border px-3 rounded-2 fw-semibold text-muted">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th class="ps-4">Tgl Pengajuan</th>
                    <th>Divisi & SPV</th>
                    <th>Vendor & Nominal</th>
                    <th>Status Keseluruhan</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result_riwayat) > 0) {
                    while ($row = mysqli_fetch_assoc($result_riwayat)) { 
                        $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                        $tgl = date('d M Y', strtotime($row['tanggal_dibuat']));
                        $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';

                        // ---------------------------------------------------------------
                        // LOGIKA PENENTUAN STATUS DETAIL (TRACKING PER TAHAP VALIDASI)
                        // ---------------------------------------------------------------
                        $status_keseluruhan = strtolower($row['status_keseluruhan'] ?? 'pending');
                        $stat_mgr           = strtolower($row['status_manager'] ?? 'pending');
                        $stat_fin           = strtolower($row['status_finance'] ?? 'pending');
                        $stat_dirut         = strtolower($row['status_dirut'] ?? 'pending');
                        $stat_dirkeu        = strtolower($row['status_dirkeu'] ?? 'pending');
                        $status_laporan     = strtolower($row['status_laporan'] ?? '');
                        $catatan            = htmlspecialchars($row['catatan_revisi'] ?? '');

                        // Deteksi fleksibel jika ada status Ditolak atau Revisi di level manapun
                        $is_ditolak = ($status_keseluruhan == 'ditolak' || $stat_mgr == 'ditolak' || $stat_fin == 'ditolak' || $stat_dirut == 'ditolak' || $stat_dirkeu == 'ditolak');
                        $is_revisi  = ($status_keseluruhan == 'revisi' || $stat_mgr == 'revisi' || $stat_fin == 'revisi' || $stat_dirut == 'revisi' || $stat_dirkeu == 'revisi');

                        $status_tampil = '';

                        if ($is_ditolak) {
                            // Cari tahu siapa yang menolak
                            $penolak = "Sistem";
                            if ($stat_mgr == 'ditolak') $penolak = "Manager";
                            elseif ($stat_fin == 'ditolak') $penolak = "Finance";
                            elseif ($stat_dirut == 'ditolak') $penolak = "Dirut";
                            elseif ($stat_dirkeu == 'ditolak') $penolak = "Dirkeu";

                            $status_tampil = '<span class="status-badge badge-ditolak"><i class="fas fa-xmark"></i> Ditolak ('.$penolak.')</span>';
                            if (!empty($catatan)) {
                                $status_tampil .= '<span class="status-catatan">Alasan: '.$catatan.'</span>';
                            }

                        } elseif ($is_revisi) {
                            // Cari tahu siapa yang meminta revisi
                            $perevisi = "";
                            if ($stat_mgr == 'revisi') $perevisi = " (Manager)";
                            elseif ($stat_fin == 'revisi') $perevisi = " (Finance)";
                            elseif ($stat_dirut == 'revisi') $perevisi = " (Dirut)";
                            elseif ($stat_dirkeu == 'revisi') $perevisi = " (Dirkeu)";

                            $status_tampil = '<span class="status-badge badge-revisi"><i class="fas fa-wrench"></i> Revisi'.$perevisi.', Menunggu SPV</span>';
                            if (!empty($catatan)) {
                                $status_tampil .= '<span class="status-catatan">Catatan: '.$catatan.'</span>';
                            }

                        } elseif ($status_laporan == 'valid') {
                            $status_tampil = '<span class="status-badge badge-selesai"><i class="fas fa-check-double"></i> Selesai (Laporan Valid)</span>';
                            if (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) {
                                $status_tampil .= '<span class="status-catatan">Realisasi: Rp ' . number_format($row['realisasi_dana'], 0, ',', '.') . '</span>';
                            }

                        } elseif ($stat_mgr != 'disetujui' && $stat_mgr != 'ditolak') {
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Menunggu (Manager)</span>';

                        } elseif ($stat_fin != 'disetujui' && $stat_fin != 'ditolak') {
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Menunggu (Finance)</span>';

                        } elseif ($stat_dirut != 'disetujui' && $stat_dirut != 'ditolak') {
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Menunggu (Dirut)</span>';

                        } elseif ($stat_dirkeu != 'disetujui' && $stat_dirkeu != 'ditolak') {
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Menunggu (Dirkeu)</span>';

                        } elseif ($stat_dirkeu == 'disetujui') {
                            // Semua level sudah menyetujui, tinggal menunggu SPV mengunggah laporan/nota pertanggungjawaban
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Menunggu Laporan (SPV)</span>';

                        } else {
                            $status_tampil = '<span class="status-badge badge-berjalan"><i class="fas fa-spinner fa-spin"></i> Sedang Berjalan</span>';
                        }
                ?>
                <tr>
                    <td class="ps-4 text-dark fw-medium"><?php echo $tgl; ?></td>
                    <td>
                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['nama_spv']); ?></strong>
                        <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($nama_divisi); ?></span>
                    </td>
                    <td>
                        <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                        <div class="nominal-text"><?php echo $nominal; ?></div>
                    </td>
                    <td><?php echo $status_tampil; ?></td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn-audit" 
                            data-bs-toggle="modal" data-bs-target="#modalDetailAudit"
                            onclick="bukaModalAudit(this)"
                            data-tgl="<?php echo date('d F Y', strtotime($row['tanggal_dibuat'])); ?>"
                            data-spv="<?php echo htmlspecialchars($row['nama_spv']); ?> (<?php echo htmlspecialchars($row['nama_divisi']); ?>)"
                            data-vendor="<?php echo htmlspecialchars($row['nama_vendor']); ?>"
                            data-nominal="<?php echo $nominal; ?>"
                            data-keterangan="<?php echo htmlspecialchars($row['keterangan']); ?>"
                            data-rekening="<?php echo htmlspecialchars($row['rekening_tujuan']); ?>"
                            data-file="<?php echo htmlspecialchars($row['file_penawaran'] ?? ''); ?>"
                            data-transfer="<?php echo htmlspecialchars($row['bukti_transfer'] ?? ''); ?>"
                            data-rincian="<?php echo htmlspecialchars($row['rincian_pembelian'] ?? ''); ?>"
                            data-nota="<?php echo htmlspecialchars($row['bukti_nota'] ?? ''); ?>"
                            data-realisasi="<?php echo (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) ? $row['realisasi_dana'] : ''; ?>"
                        ><i class="fas fa-magnifying-glass-chart me-1"></i> Detail Audit</button>
                    </td>
                </tr>
                <?php 
                    } 
                } else { 
                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='fas fa-folder-open fa-3x mb-3 opacity-25 d-block'></i> Data riwayat tidak ditemukan.</td></tr>"; 
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Render Pagination Bawah -->
    <?php if ($total_halaman > 1): ?>
    <div class="card-footer bg-white p-3 border-top-0">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($halaman_aktif > 1): ?>
                    <li class="page-item"><a class="page-link" href="?halaman=<?php echo $halaman_aktif - 1; ?><?php echo $parameter_url; ?>">&laquo; Prev</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_halaman; $i++): $active = ($i == $halaman_aktif) ? "active" : ""; ?>
                    <li class="page-item <?php echo $active; ?>"><a class="page-link" href="?halaman=<?php echo $i; ?><?php echo $parameter_url; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>

                <?php if ($halaman_aktif < $total_halaman): ?>
                    <li class="page-item"><a class="page-link" href="?halaman=<?php echo $halaman_aktif + 1; ?><?php echo $parameter_url; ?>">Next &raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Audit Lengkap Bootstrap 5 -->
<div class="modal fade" id="modalDetailAudit" tabindex="-1" aria-labelledby="modalDetailAuditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="modalDetailAuditLabel" style="color: var(--text-main);">
                    <i class="fas fa-file-shield text-info me-2"></i> Mode Detail Audit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                
                <div class="row g-4">
                    <!-- Kolom Kiri: Data Awal -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-muted mb-3" style="font-size: 0.8rem; text-transform: uppercase;">A. Data Pengajuan Awal</h6>
                        <table class="table table-borderless table-sm mb-0" style="font-size: 0.875rem;">
                            <tr><td width="40%" class="text-muted">Tanggal</td><td id="det_tgl" class="fw-semibold text-dark"></td></tr>
                            <tr><td class="text-muted">Diajukan Oleh</td><td id="det_spv" class="fw-semibold text-dark"></td></tr>
                            <tr><td class="text-muted">Vendor Tujuan</td><td id="det_vendor" class="fw-bold text-dark"></td></tr>
                            <tr><td class="text-muted align-top">Rekening</td><td id="det_rekening" class="font-monospace text-dark bg-light p-1 rounded"></td></tr>
                            <tr><td class="text-muted align-top">Keterangan</td><td id="det_keterangan" class="text-dark fst-italic"></td></tr>
                            <tr><td class="text-muted align-middle border-top mt-2 pt-2">File Penawaran</td><td id="det_file" class="border-top mt-2 pt-2"></td></tr>
                        </table>
                    </div>
                    
                    <!-- Kolom Kanan: Pasca Pencairan -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3" style="font-size: 0.8rem; text-transform: uppercase;">B. Pencairan & Pelaporan</h6>
                        <table class="table table-borderless table-sm mb-0" style="font-size: 0.875rem;">
                            <tr><td width="40%" class="text-muted">Nominal ACC</td><td id="det_nominal" class="fw-bold text-success fs-6"></td></tr>
                            <tr><td class="text-muted align-middle">Bukti Transfer</td><td id="det_transfer"></td></tr>
                            <tr><td class="text-muted align-top border-top mt-2 pt-2">Rincian SPV</td><td class="border-top mt-2 pt-2"><div id="det_rincian" style="white-space: pre-wrap; font-family: monospace; font-size:0.75rem; background: #f8fafc; padding: 8px; border-radius: 6px; border: 1px dashed #cbd5e1;"></div></td></tr>
                            <tr><td class="text-muted align-middle">Nota Laporan</td><td id="det_nota"></td></tr>
                            <tr id="det_realisasi_wrapper" class="d-none"><td class="text-muted align-middle border-top mt-2 pt-2">Realisasi Dana</td><td id="det_realisasi" class="fw-bold text-success border-top mt-2 pt-2"></td></tr>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-weight: 600; border-radius: 8px;">Tutup Panel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function bukaModalAudit(btn) {
        document.getElementById('det_tgl').innerText = btn.getAttribute('data-tgl');
        document.getElementById('det_spv').innerText = btn.getAttribute('data-spv');
        document.getElementById('det_vendor').innerText = btn.getAttribute('data-vendor');
        document.getElementById('det_nominal').innerText = btn.getAttribute('data-nominal');
        document.getElementById('det_keterangan').innerText = btn.getAttribute('data-keterangan');
        document.getElementById('det_rekening').innerText = btn.getAttribute('data-rekening');

        let realisasi = btn.getAttribute('data-realisasi');
        let realisasiWrapper = document.getElementById('det_realisasi_wrapper');
        if (realisasi && realisasi.trim() !== '') {
            document.getElementById('det_realisasi').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(parseFloat(realisasi));
            realisasiWrapper.classList.remove('d-none');
        } else {
            realisasiWrapper.classList.add('d-none');
        }
        
        let filePenawaran = btn.getAttribute('data-file');
        document.getElementById('det_file').innerHTML = filePenawaran ? `<a href="../uploads/penawaran/${filePenawaran}" target="_blank" class="link-berkas"><i class="fas fa-file-pdf"></i> Dokumen</a>` : '<span class="text-muted fst-italic" style="font-size: 0.8rem;">Tidak ada</span>';

        let buktiTransfer = btn.getAttribute('data-transfer');
        document.getElementById('det_transfer').innerHTML = buktiTransfer ? `<a href="../uploads/transfer/${buktiTransfer}" target="_blank" class="link-berkas link-tf"><i class="fas fa-money-bill-transfer"></i> Bukti TF</a>` : '<span class="text-muted fst-italic" style="font-size: 0.8rem;">Belum ditransfer</span>';

        let rincian = btn.getAttribute('data-rincian');
        document.getElementById('det_rincian').innerText = rincian ? rincian : 'Belum ada rincian pembelian dari SPV.';

        let fileNota = btn.getAttribute('data-nota');
        if (fileNota) {
            let notaArray = fileNota.split(',');
            let notaHtml = '';
            notaArray.forEach((nota, index) => {
                if (nota.trim() !== '') {
                    notaHtml += `<a href="../uploads/nota/${nota.trim()}" target="_blank" class="link-berkas"><i class="fas fa-receipt"></i> Nota ${index+1}</a>`;
                }
            });
            document.getElementById('det_nota').innerHTML = notaHtml !== '' ? notaHtml : '<span class="text-muted fst-italic" style="font-size: 0.8rem;">Belum ada nota</span>';
        } else {
            document.getElementById('det_nota').innerHTML = '<span class="text-muted fst-italic" style="font-size: 0.8rem;">Belum ada nota</span>';
        }
    }
</script>

<?php require 'footer.php'; ?>