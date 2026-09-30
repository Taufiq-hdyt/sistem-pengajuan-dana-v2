<?php
session_start();
require '../config/koneksi.php';

// =========================================================================
// 1. LOGIKA BACKEND: PROSES REVISI PENGAJUAN OLEH SPV/USER
// =========================================================================
if (isset($_POST['submit_revisi'])) {
    $id_pengajuan    = $_POST['id_pengajuan'];
    $vendor          = htmlspecialchars($_POST['nama_vendor']);
    $jatuh_tempo     = $_POST['tanggal_jatuh_tempo'];
    $biaya           = $_POST['biaya_diajukan'];
    $rekening        = htmlspecialchars($_POST['rekening_tujuan']);
    $keterangan      = htmlspecialchars($_POST['keterangan']);

    // -------------------------------------------------------------------
    // VALIDASI: Tanggal Jatuh Tempo tidak boleh tanggal yang sudah lewat
    // -------------------------------------------------------------------
    $hari_ini = date('Y-m-d');
    if (empty($jatuh_tempo) || $jatuh_tempo < $hari_ini) {
        $_SESSION['swal_msg'] = "Tanggal jatuh tempo tidak boleh tanggal yang sudah lewat. Silakan pilih tanggal hari ini atau setelahnya.";
        $_SESSION['swal_icon'] = "error";
        header("Location: riwayat_pengajuan.php");
        exit();
    }
    
    $file_names_array = [];
    $upload_dir       = '../uploads/penawaran/';
    $allowed_ext      = ['jpg', 'jpeg', 'png', 'pdf'];
    $file_diupdate    = false;

    if (isset($_FILES['file_penawaran']['name'][0]) && $_FILES['file_penawaran']['name'][0] != "") {
        $total_files = count($_FILES['file_penawaran']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $file_name = $_FILES['file_penawaran']['name'][$i];
            $file_tmp  = $_FILES['file_penawaran']['tmp_name'][$i];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_size = $_FILES['file_penawaran']['size'][$i];

            if (in_array($file_ext, $allowed_ext) && $file_size <= 5000000) {
                $new_file_name = time() . "_" . uniqid() . "." . $file_ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $file_names_array[] = $new_file_name;
                }
            }
        }
        if (count($file_names_array) > 0) {
            $file_diupdate = true;
            $gabungan_nama_file = implode(',', $file_names_array);
        }
    }

    // Reset seluruh status kembali ke Pending agar dapat diproses ulang oleh Manager
    if ($file_diupdate) {
        $query = "UPDATE pengajuan SET 
                  nama_vendor = ?, tanggal_jatuh_tempo = ?, biaya_diajukan = ?, 
                  rekening_tujuan = ?, keterangan = ?, 
                  status_keseluruhan = 'Pending', status_manager = 'Pending', 
                  status_finance = 'Pending', status_dirut = 'Pending', status_dirkeu = 'Pending',
                  file_penawaran = ? 
                  WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        // Urutan variabel: vendor(s), tempo(s), biaya(d), rekening(s), keterangan(s), file(s), id(i) => ssdsssi
        $stmt->bind_param("ssdsssi", $vendor, $jatuh_tempo, $biaya, $rekening, $keterangan, $gabungan_nama_file, $id_pengajuan);
    } else {
        $query = "UPDATE pengajuan SET 
                  nama_vendor = ?, tanggal_jatuh_tempo = ?, biaya_diajukan = ?, 
                  rekening_tujuan = ?, keterangan = ?, 
                  status_keseluruhan = 'Pending', status_manager = 'Pending', 
                  status_finance = 'Pending', status_dirut = 'Pending', status_dirkeu = 'Pending' 
                  WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        // Urutan variabel: vendor(s), tempo(s), biaya(d), rekening(s), keterangan(s), id(i) => ssdssi
        $stmt->bind_param("ssdssi", $vendor, $jatuh_tempo, $biaya, $rekening, $keterangan, $id_pengajuan);
    }

    if ($stmt->execute()) {
        $_SESSION['swal_msg'] = "Pengajuan berhasil diperbaiki dan dikirim ulang!";
        $_SESSION['swal_icon'] = "success";
    } else {
        $_SESSION['swal_msg'] = "Gagal menyimpan perubahan: " . $koneksi->error;
        $_SESSION['swal_icon'] = "error";
    }
    header("Location: riwayat_pengajuan.php");
    exit();
}

require 'header.php'; 
?>

<!-- ========================================================================= -->
<!-- 2. UI HALAMAN UTAMA: TABEL DATA PENGAJUAN                                 -->
<!-- ========================================================================= -->
<style>
    .table-custom th {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 12px;
    }
    .table-custom td {
        vertical-align: middle;
        padding-top: 1.2rem;
        padding-bottom: 1.2rem;
        border-bottom: 1px solid #f8f9fa;
    }
    .badge-soft-warning {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: #f0f9ff;
        color: #0284c7;
        border: 1px solid #bae6fd;
        font-weight: 600;
    }
    .badge-soft-success {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        font-weight: 600;
    }
    .btn-outline-custom {
        color: #64748b;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        font-weight: 500;
        font-size: 0.85rem;
    }
    .btn-outline-custom:hover {
        background-color: #f8fafc;
        color: #334155;
    }
    .status-stack .badge { margin-bottom: 4px; }
    .status-catatan-laporan {
        font-size: 0.75rem;
        line-height: 1.2;
        max-width: 200px;
        margin: 0 auto;
        color: #d97706 !important;
        font-weight: 500;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0" style="color: #1e293b; font-size: 1.25rem;">
                    <i class="fas fa-history text-primary me-2"></i> Riwayat & Status Pengajuan Anda
                </h4>
                <a href="tambah_pengajuan.php" class="btn btn-primary btn-sm rounded-pill px-3 py-2 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Buat Pengajuan
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase">Tgl Dibuat</th>
                            <th class="text-uppercase">Vendor & Nominal</th>
                            <th class="text-uppercase text-center">Status Saat Ini</th>
                            <th class="text-uppercase text-center">Aksi / Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $user_id_aktif = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 1; 
                        
                        $query = "SELECT * FROM pengajuan WHERE user_id = ? ORDER BY id DESC";
                        $stmt = $koneksi->prepare($query);
                        $stmt->bind_param("i", $user_id_aktif);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                                $tgl_dibuat = date('d M Y', strtotime($row['tanggal_dibuat'] ?? date('Y-m-d')));
                                
                                // LOGIKA PENENTUAN STATUS DETAIL
                                $status_keseluruhan = strtolower($row['status_keseluruhan'] ?? 'pending');
                                $stat_mgr           = strtolower($row['status_manager'] ?? 'pending');
                                $stat_fin           = strtolower($row['status_finance'] ?? 'pending');
                                $stat_dirut         = strtolower($row['status_dirut'] ?? 'pending');
                                $stat_dirkeu        = strtolower($row['status_dirkeu'] ?? 'pending');
                                $status_laporan     = strtolower($row['status_laporan'] ?? '');
                                $catatan            = htmlspecialchars($row['catatan_revisi'] ?? '');
                                // Catatan revisi laporan (kolom terpisah kalau ada, fallback ke catatan_revisi)
                                $catatan_laporan    = htmlspecialchars($row['catatan_laporan'] ?? $row['catatan_revisi'] ?? '');
                                
                                // Deteksi fleksibel jika ada status Ditolak atau Revisi di level manapun (tahap persetujuan)
                                $is_ditolak = ($status_keseluruhan == 'ditolak' || $stat_mgr == 'ditolak' || $stat_fin == 'ditolak' || $stat_dirut == 'ditolak' || $stat_dirkeu == 'ditolak');
                                $is_revisi  = ($status_keseluruhan == 'revisi' || $stat_mgr == 'revisi' || $stat_fin == 'revisi' || $stat_dirut == 'revisi' || $stat_dirkeu == 'revisi');

                                // Deteksi progres tahap pencairan & pelaporan (setelah semua pihak menyetujui)
                                $sudah_transfer = !empty(trim($row['bukti_transfer'] ?? ''));
                                $sudah_lapor    = !empty(trim($row['bukti_nota'] ?? '')) || !empty(trim($row['rincian_pembelian'] ?? ''));
                                $is_revisi_laporan = ($status_laporan == 'revisi');

                                $status_html = "";
                                
                                if ($is_ditolak) {
                                    // Cari tahu siapa yang menolak
                                    $penolak = "Sistem";
                                    if ($stat_mgr == 'ditolak') $penolak = "Manager";
                                    elseif ($stat_fin == 'ditolak') $penolak = "Finance";
                                    elseif ($stat_dirut == 'ditolak') $penolak = "Dirut";
                                    elseif ($stat_dirkeu == 'ditolak') $penolak = "Dirkeu";

                                    $status_html = '<span class="badge rounded-pill badge-soft-danger px-3 py-2 mb-1"><i class="fas fa-times me-1"></i> Ditolak (' . $penolak . ')</span>';
                                    
                                    if(!empty($catatan)) {
                                        $status_html .= '<div class="text-danger mt-1 fw-medium" style="font-size: 0.75rem; line-height: 1.2; max-width: 200px; margin: 0 auto;">Alasan: ' . $catatan . '</div>';
                                    }

                                } elseif ($is_revisi) {
                                    // Cari tahu siapa yang meminta revisi (tahap persetujuan, sebelum sampai Dirkeu)
                                    $perevisi = "";
                                    if ($stat_mgr == 'revisi') $perevisi = " (Manager)";
                                    elseif ($stat_fin == 'revisi') $perevisi = " (Finance)";

                                    $status_html = '<span class="badge rounded-pill badge-soft-warning px-3 py-2 mb-1"><i class="fas fa-exclamation-circle me-1"></i> Perlu Revisi' . $perevisi . '</span>';
                                    if(!empty($catatan)) {
                                        $status_html .= '<div class="text-warning mt-1 fw-medium" style="font-size: 0.75rem; line-height: 1.2; max-width: 200px; margin: 0 auto; color: #d97706 !important;">Catatan: ' . $catatan . '</div>';
                                    }

                                } elseif ($stat_dirkeu == 'disetujui') {
                                    // ------------------------------------------------------------
                                    // Semua pihak (Manager, Finance, Dirut, Dirkeu) sudah menyetujui.
                                    // Lanjut ke tahap pencairan & pelaporan pertanggungjawaban.
                                    // ------------------------------------------------------------
                                    if ($status_laporan == 'valid') {
                                        // Tahap akhir: laporan sudah divalidasi Finance
                                        $status_html = '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Pengajuan Selesai</span>';
                                        if (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) {
                                            $status_html .= '<div class="text-muted mt-1" style="font-size: 0.75rem;">Realisasi: Rp ' . number_format($row['realisasi_dana'], 0, ',', '.') . '</div>';
                                        }

                                    } elseif ($is_revisi_laporan) {
                                        // Finance meminta revisi atas laporan pertanggungjawaban SPV
                                        $status_html  = '<div class="status-stack">';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Disetujui Final</span>';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-exclamation-circle me-1"></i> Revisi Laporan (Finance)</span>';
                                        $status_html .= '</div>';
                                        if (!empty($catatan_laporan)) {
                                            $status_html .= '<div class="status-catatan-laporan">Catatan: ' . $catatan_laporan . '</div>';
                                        }

                                    } elseif ($sudah_lapor) {
                                        // SPV sudah upload nota/rincian pembelian, menunggu divalidasi Finance
                                        $status_html  = '<div class="status-stack">';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Disetujui Final</span>';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu Validasi Finance</span>';
                                        $status_html .= '</div>';

                                    } elseif ($sudah_transfer) {
                                        // Finance sudah transfer dana, menunggu SPV upload bukti pembelian
                                        $status_html  = '<div class="status-stack">';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Disetujui Final</span>';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-money-bill-transfer me-1"></i> Sudah Ditransfer Finance</span>';
                                        $status_html .= '</div>';

                                    } else {
                                        // Baru saja disetujui final, dana belum ditransfer
                                        $status_html  = '<div class="status-stack">';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Disetujui Final</span>';
                                        $status_html .= '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-clock me-1"></i> Menunggu Ditransfer Finance</span>';
                                        $status_html .= '</div>';
                                    }

                                } else {
                                    // Cek berurutan tiap tahap validasi: Manager -> Finance -> Dirut -> Dirkeu
                                    if ($stat_mgr != 'disetujui' && $stat_mgr != 'ditolak') {
                                        $status_html = '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu (Manager)</span>';
                                    } elseif ($stat_fin != 'disetujui' && $stat_fin != 'ditolak') {
                                        $status_html = '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu (Finance)</span>';
                                    } elseif ($stat_dirut != 'disetujui' && $stat_dirut != 'ditolak') {
                                        $status_html = '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu (Dirut)</span>';
                                    } elseif ($stat_dirkeu != 'disetujui' && $stat_dirkeu != 'ditolak') {
                                        $status_html = '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu (Dirkeu)</span>';
                                    } else {
                                        $status_html = '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Memproses...</span>';
                                    }
                                }
                        ?>
                        <tr>
                            <td class="text-dark fw-medium" style="font-size: 0.95rem;"><?= $tgl_dibuat; ?></td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 1rem;"><?= htmlspecialchars($row['nama_vendor']); ?></div>
                                <div class="text-success fw-bold" style="font-size: 0.9rem;"><?= $nominal; ?></div>
                            </td>
                            <td class="text-center align-middle">
                                <?= $status_html; ?>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-outline-custom rounded-pill px-3 py-1" 
                                    data-bs-toggle="modal" data-bs-target="#modalDetail"
                                    data-id="<?= $row['id']; ?>"
                                    data-tgl="<?= $tgl_dibuat; ?>"
                                    data-vendor="<?= htmlspecialchars($row['nama_vendor']); ?>"
                                    data-jenis="<?= htmlspecialchars($row['jenis_pengajuan'] ?? '-'); ?>"
                                    data-nominal="<?= $nominal; ?>"
                                    data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>"
                                    data-tempo="<?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?>"
                                    data-rekening="<?= htmlspecialchars($row['rekening_tujuan']); ?>"
                                    data-status="<?= $is_revisi ? 'revisi' : ($is_ditolak ? 'ditolak' : ($is_revisi_laporan ? 'revisi' : $status_keseluruhan)); ?>"
                                    data-alasan="<?= $is_revisi_laporan ? $catatan_laporan : htmlspecialchars($row['catatan_revisi'] ?? ''); ?>"
                                    data-file="<?= htmlspecialchars($row['file_penawaran'] ?? ''); ?>"
                                    data-transfer="<?= htmlspecialchars($row['bukti_transfer'] ?? ''); ?>"
                                    data-nota="<?= htmlspecialchars($row['bukti_nota'] ?? ''); ?>"
                                    data-realisasi="<?= (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) ? $row['realisasi_dana'] : ''; ?>"
                                    onclick="isiModalDetail(this)">
                                    <i class="fas fa-eye me-1"></i> Detail
                                </button>
                                
                                <?php if ($is_revisi): ?>
                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 ms-1 text-white shadow-sm" 
                                    data-bs-toggle="modal" data-bs-target="#modalPerbaikan"
                                    data-id="<?= $row['id']; ?>"
                                    data-vendor="<?= htmlspecialchars($row['nama_vendor']); ?>"
                                    data-nominal="<?= $row['biaya_diajukan']; ?>"
                                    data-tempo="<?= $row['tanggal_jatuh_tempo']; ?>"
                                    data-rekening="<?= htmlspecialchars($row['rekening_tujuan']); ?>"
                                    data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>"
                                    data-catatan="<?= htmlspecialchars($row['catatan_revisi'] ?? ''); ?>"
                                    onclick="isiModalPerbaikan(this)">
                                    <i class="fas fa-wrench"></i> Perbaiki
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 mb-3 d-block text-light"></i>
                                Belum ada data pengajuan.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. MODAL DIALOG (DETAIL & PERBAIKAN REVISI)                               -->
<!-- ========================================================================= -->

<!-- Modal Detail Umum -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Detail Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                
                <!-- Notifikasi Alasan Ditolak / Revisi -->
                <div id="det_alasan_wrapper" class="alert alert-danger mb-3 d-none" style="border-radius: 12px; border: 1px dashed #fca5a5; background-color: #fef2f2;">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-circle-xmark fs-5 me-2 mt-1 text-danger"></i>
                        <div>
                            <strong class="d-block text-danger mb-1" id="det_alasan_title">Catatan / Alasan:</strong>
                            <span id="det_alasan" class="text-dark" style="font-size: 0.875rem;"></span>
                        </div>
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Diajukan</span>
                    <h3 id="det_nominal" style="color: #008a3d; margin: 0; font-weight: 800;"></h3>
                </div>
                
                <table class="table table-borderless table-sm mb-0" style="font-size: 0.875rem;">
                    <tr><td width="35%" class="text-muted fw-medium">Tgl Dibuat</td><td width="5%">:</td><td id="det_tgl" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Vendor</td><td>:</td><td id="det_vendor" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Jenis (PPN)</td><td>:</td><td><span id="det_jenis" class="badge bg-secondary bg-opacity-10 text-secondary border"></span></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Keterangan</td><td class="align-top">:</td><td id="det_keterangan" class="text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Jatuh Tempo</td><td>:</td><td id="det_tempo" class="fw-semibold text-danger"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Rekening</td><td class="align-top">:</td><td id="det_rekening" class="font-monospace text-dark bg-light p-1 rounded"></td></tr>
                    <tr><td class="text-muted fw-medium mt-2 pt-2 border-top">File Penawaran</td><td class="mt-2 pt-2 border-top">:</td><td id="det_file" class="mt-2 pt-2 border-top"></td></tr>
                    <tr><td class="text-muted fw-medium">Bukti Transfer</td><td>:</td><td id="det_transfer"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Bukti Nota</td><td class="align-top">:</td><td id="det_nota"></td></tr>
                    <tr id="det_realisasi_wrapper" class="d-none"><td class="text-muted fw-medium">Realisasi Dana</td><td>:</td><td id="det_realisasi" class="fw-bold text-success"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Perbaikan (Revisi) -->
<div class="modal fade" id="modalPerbaikan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-warning" style="color: #d97706 !important;"><i class="fas fa-wrench me-2"></i> Perbaiki Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body px-4 pt-3 pb-2">
                    
                    <div class="alert alert-warning d-flex align-items-start mb-4" style="border-radius: 12px; border: 1px dashed #fcd34d;">
                        <i class="fas fa-info-circle fs-5 me-3 mt-1 text-warning"></i>
                        <div>
                            <strong class="d-block mb-1">Catatan Validator:</strong>
                            <span id="rev_catatan" style="font-size: 0.85rem; font-family: monospace;"></span>
                        </div>
                    </div>

                    <input type="hidden" name="id_pengajuan" id="rev_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Nama Vendor</label>
                            <input type="text" name="nama_vendor" id="rev_vendor" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Jatuh Tempo</label>
                            <input type="date" name="tanggal_jatuh_tempo" id="rev_tempo" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Tidak bisa memilih tanggal yang sudah lewat.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Total Biaya (Rp)</label>
                            <input type="number" name="biaya_diajukan" id="rev_nominal" class="form-control" required min="1000" step="any">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Rekening Tujuan</label>
                            <input type="text" name="rekening_tujuan" id="rev_rekening" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Keterangan / Keperluan</label>
                        <textarea name="keterangan" id="rev_keterangan" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-2 p-3 rounded-3" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label text-primary mb-0" style="font-size: 0.85rem; font-weight: 600;">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Upload Ulang Penawaran Harga
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="tambahFileRowRevisi()" style="font-size: 0.75rem;">
                                <i class="fas fa-plus"></i> Tambah File
                            </button>
                        </div>
                        
                        <div id="fileUploadContainerRevisi">
                            <div class="input-group mb-2 file-row-revisi">
                                <input type="file" name="file_penawaran[]" class="form-control form-control-sm" accept=".jpg, .jpeg, .png, .pdf">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusFileRowRevisi(this)" disabled><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">* Biarkan kosong jika file lama tidak perlu diganti.</small>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 mt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_revisi" class="btn btn-warning text-white px-4" style="background: #f59e0b; font-weight: 600;">Kirim Ulang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. JAVASCRIPT LENGKAP                                                     -->
<!-- ========================================================================= -->
<script>
    function isiModalDetail(btn) {
        document.getElementById('det_tgl').innerText = btn.getAttribute('data-tgl');
        document.getElementById('det_vendor').innerText = btn.getAttribute('data-vendor');
        document.getElementById('det_jenis').innerText = btn.getAttribute('data-jenis');
        document.getElementById('det_nominal').innerText = btn.getAttribute('data-nominal');
        document.getElementById('det_keterangan').innerText = btn.getAttribute('data-keterangan');
        document.getElementById('det_tempo').innerText = btn.getAttribute('data-tempo');
        document.getElementById('det_rekening').innerText = btn.getAttribute('data-rekening');

        let realisasi = btn.getAttribute('data-realisasi');
        let realisasiWrapper = document.getElementById('det_realisasi_wrapper');
        if (realisasi && realisasi.trim() !== '') {
            let angkaRealisasi = parseFloat(realisasi);
            document.getElementById('det_realisasi').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(angkaRealisasi);
            realisasiWrapper.classList.remove('d-none');
        } else {
            realisasiWrapper.classList.add('d-none');
        }

        let status = (btn.getAttribute('data-status') || '').toLowerCase().trim();
        let alasan = btn.getAttribute('data-alasan');
        let alasanWrapper = document.getElementById('det_alasan_wrapper');
        let alasanTitle = document.getElementById('det_alasan_title');
        let alasanText = document.getElementById('det_alasan');

        if (status === 'ditolak' || status === 'revisi' || (alasan && alasan.trim() !== '')) {
            alasanTitle.innerText = (status === 'revisi' ? "Catatan Perbaikan:" : "Alasan Penolakan:");
            alasanText.innerText = (alasan && alasan.trim() !== '') ? alasan : "Tidak ada catatan spesifik dari validator.";
            alasanWrapper.classList.remove('d-none');
            
            if(status === 'revisi'){
                alasanWrapper.style.border = "1px dashed #fcd34d";
                alasanWrapper.style.backgroundColor = "#fffbeb";
                alasanTitle.classList.replace('text-danger', 'text-warning');
                alasanWrapper.querySelector('i').classList.replace('fa-circle-xmark', 'fa-info-circle');
                alasanWrapper.querySelector('i').classList.replace('text-danger', 'text-warning');
            } else {
                alasanWrapper.style.border = "1px dashed #fca5a5";
                alasanWrapper.style.backgroundColor = "#fef2f2";
                alasanTitle.classList.replace('text-warning', 'text-danger');
                alasanWrapper.querySelector('i').classList.replace('fa-info-circle', 'fa-circle-xmark');
                alasanWrapper.querySelector('i').classList.replace('text-warning', 'text-danger');
            }
        } else {
            alasanWrapper.classList.add('d-none');
        }
        
        let filePenawaran = btn.getAttribute('data-file');
        if (filePenawaran && filePenawaran.trim() !== '') {
            let fileArray = filePenawaran.split(',');
            let fileHtml = '';
            fileArray.forEach((file, index) => {
                let trimmedFile = file.trim();
                if (trimmedFile !== '') {
                    fileHtml += `<a href="../uploads/penawaran/${encodeURIComponent(trimmedFile)}" target="_blank" class="btn btn-sm btn-outline-primary me-1 mb-1"><i class="fas fa-file-pdf me-1"></i> Berkas ${index+1}</a> `;
                }
            });
            document.getElementById('det_file').innerHTML = fileHtml !== '' ? fileHtml : '<span class="text-muted fst-italic">Tidak dilampirkan</span>';
        } else {
            document.getElementById('det_file').innerHTML = '<span class="text-muted fst-italic">Tidak dilampirkan</span>';
        }

        let buktiTransfer = btn.getAttribute('data-transfer');
        document.getElementById('det_transfer').innerHTML = (buktiTransfer && buktiTransfer.trim() !== '') ? '<a href="../uploads/transfer/' + encodeURIComponent(buktiTransfer) + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-money-bill-transfer me-1"></i> Bukti TF</a>' : '<span class="text-muted fst-italic">Belum ditransfer</span>';

        let fileNota = btn.getAttribute('data-nota');
        if (fileNota && fileNota.trim() !== '') {
            let notaArray = fileNota.split(',');
            let notaHtml = '';
            notaArray.forEach((nota, index) => {
                let trimmedNota = nota.trim();
                if (trimmedNota !== '') {
                    notaHtml += `<a href="../uploads/nota/${encodeURIComponent(trimmedNota)}" target="_blank" class="btn btn-sm btn-outline-info me-1 mb-1"><i class="fas fa-receipt me-1"></i> Nota ${index+1}</a> `;
                }
            });
            document.getElementById('det_nota').innerHTML = notaHtml !== '' ? notaHtml : '<span class="text-muted fst-italic">Belum ada nota</span>';
        } else {
            document.getElementById('det_nota').innerHTML = '<span class="text-muted fst-italic">Belum ada nota</span>';
        }
    }

    function isiModalPerbaikan(btn) {
        document.getElementById('rev_id').value = btn.getAttribute('data-id');
        document.getElementById('rev_vendor').value = btn.getAttribute('data-vendor');
        document.getElementById('rev_nominal').value = btn.getAttribute('data-nominal');
        document.getElementById('rev_rekening').value = btn.getAttribute('data-rekening');
        document.getElementById('rev_keterangan').value = btn.getAttribute('data-keterangan');

        // ---- Validasi tanggal jatuh tempo tidak boleh yang sudah lewat ----
        const tempoInput  = document.getElementById('rev_tempo');
        const todayStr    = new Date().toISOString().split('T')[0]; // format YYYY-MM-DD
        tempoInput.min    = todayStr; // kunci ulang min setiap modal dibuka (jaga-jaga lintas hari)

        const tempoLama   = btn.getAttribute('data-tempo'); // format YYYY-MM-DD dari database
        // Jika tanggal lama sudah lewat, jangan diisi otomatis dengan tanggal expired,
        // biarkan kosong supaya SPV wajib memilih tanggal baru yang valid.
        tempoInput.value  = (tempoLama && tempoLama >= todayStr) ? tempoLama : '';
        
        let catatan = btn.getAttribute('data-catatan');
        document.getElementById('rev_catatan').innerText = (catatan && catatan.trim() !== '') ? catatan : "Tidak ada catatan spesifik.";
        resetFileContainerRevisi();
    }

    function tambahFileRowRevisi() {
        const container = document.getElementById('fileUploadContainerRevisi');
        const row = document.createElement('div');
        row.className = 'input-group mb-2 file-row-revisi';
        row.innerHTML = `
            <input type="file" name="file_penawaran[]" class="form-control form-control-sm" accept=".jpg, .jpeg, .png, .pdf">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusFileRowRevisi(this)"><i class="fas fa-trash"></i></button>
        `;
        row.style.opacity = "0";
        container.appendChild(row);
        setTimeout(() => row.style.opacity = "1", 50);
        row.style.transition = "opacity 0.3s ease";
        updateDeleteButtonsRevisi();
    }

    function hapusFileRowRevisi(btn) {
        const row = btn.closest('.file-row-revisi');
        row.style.opacity = "0";
        setTimeout(() => {
            row.remove();
            updateDeleteButtonsRevisi();
        }, 300);
    }

    function updateDeleteButtonsRevisi() {
        const rows = document.querySelectorAll('.file-row-revisi');
        const deleteBtns = document.querySelectorAll('.file-row-revisi .btn-outline-danger');
        if (rows.length === 1) {
            deleteBtns[0].disabled = true;
        } else {
            deleteBtns.forEach(btn => btn.disabled = false);
        }
    }

    function resetFileContainerRevisi() {
        const container = document.getElementById('fileUploadContainerRevisi');
        container.innerHTML = `
            <div class="input-group mb-2 file-row-revisi">
                <input type="file" name="file_penawaran[]" class="form-control form-control-sm" accept=".jpg, .jpeg, .png, .pdf">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusFileRowRevisi(this)" disabled><i class="fas fa-trash"></i></button>
            </div>
        `;
    }
</script>

<?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if(typeof Swal !== 'undefined'){
                Swal.fire({
                    title: 'Informasi',
                    text: <?php echo json_encode($_SESSION['swal_msg']); ?>,
                    icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
                    confirmButtonColor: '#008a3d'
                });
            }
        });
    </script>
<?php 
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_icon']);
endif; 
?>

<?php require 'footer.php'; ?>