<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dirkeu') {
    header("Location: ../index.php");
    exit();
}

$nama_user = $_SESSION['nama'];

// Statistik dashboard
$total_pending = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT COUNT(id) as total
         FROM pengajuan
         WHERE status_dirut = 'Disetujui'
         AND status_dirkeu = 'Pending'
         AND status_keseluruhan = 'Pending'"
    )
)['total'];

$total_disetujui = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT COUNT(id) as total
         FROM pengajuan
         WHERE status_dirkeu = 'Disetujui'"
    )
)['total'];

$total_ditolak = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT COUNT(id) as total
         FROM pengajuan
         WHERE status_dirkeu = 'Ditolak'"
    )
)['total'];

// Ambil data riwayat yang sudah diproses oleh Dirkeu
$query_riwayat = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi
                  FROM pengajuan p
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN divisi d ON u.divisi_id = d.id
                  WHERE p.status_dirkeu != 'Pending'
                  ORDER BY p.tanggal_dibuat DESC";

$result_riwayat = mysqli_query($koneksi, $query_riwayat);

// ==========================================
// RINGKASAN SERAPAN ANGGARAN BULAN BERJALAN
// ==========================================
// Serapan Anggaran = jumlah REALISASI DANA dari pengajuan yang laporannya
// sudah SELESAI/VALID (bukan dana yang diajukan, dan bukan sekadar disetujui Dirkeu).
// Detail lengkap (grafik per divisi, filter, tabel rincian) ada di halaman serapan_anggaran.php.
$bulan_ini = date('Y-m');
$query_ringkasan_bulan_ini = "SELECT COUNT(p.id) AS jumlah_transaksi, SUM(p.realisasi_dana) AS total_realisasi
                              FROM pengajuan p
                              WHERE p.status_laporan = 'Valid'
                              AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$bulan_ini'";
$result_ringkasan_bulan_ini = mysqli_query($koneksi, $query_ringkasan_bulan_ini);
$ringkasan_bulan_ini = mysqli_fetch_assoc($result_ringkasan_bulan_ini);

$total_realisasi_bulan_ini  = $ringkasan_bulan_ini['total_realisasi'] ?? 0;
$jumlah_transaksi_bulan_ini = $ringkasan_bulan_ini['jumlah_transaksi'] ?? 0;
// ==========================================

// ==========================================
// TABEL PRIORITAS: Menunggu Keputusan Dirkeu
// ==========================================
// Mengambil 5 pengajuan teratas yang sudah disetujui Dirut dan menunggu keputusan Dirkeu,
// diurutkan berdasarkan jatuh tempo paling dekat (sama pola dengan dashboard Dirut).
$query_prioritas = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi
                    FROM pengajuan p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN divisi d ON u.divisi_id = d.id
                    WHERE p.status_dirut = 'Disetujui' AND p.status_dirkeu = 'Pending' AND p.status_keseluruhan = 'Pending'
                    ORDER BY p.tanggal_jatuh_tempo ASC LIMIT 5";
$result_prioritas = mysqli_query($koneksi, $query_prioritas);
// ==========================================

require 'header.php';
?>

<style>
    /* Welcome box hijau menarik */
    .welcome-box {
        padding: 1.25rem;
        border-radius: 0.375rem;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.02);
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff;
        margin-bottom: 24px;
    }
    /* Style kartu statistik tetap sama */
    .stat-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
        height: 100%;
        overflow: hidden;
    }
    .stat-card .card-body {
        padding: 22px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 15px;
    }
    .stat-title {
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .stat-number {
        font-size: 30px;
        font-weight: 700;
        color: #212529;
        line-height: 1.2;
    }

    /* Elemen Serapan Anggaran (dipakai di dalam kartu history-card kolom kiri) */
    .serapan-icon {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        background: #dcfce7;
        color: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .serapan-title {
        font-size: 13px;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .serapan-number {
        font-size: 1.6rem;
        font-weight: 800;
        color: #16a34a;
        line-height: 1.2;
        margin: 0 0 4px 0;
    }
    .serapan-sub {
        font-size: 13px;
        color: #6c757d;
        margin-top: 2px;
    }
    .btn-serapan-detail {
        display: inline-block;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-serapan-detail:hover {
        background: #dcfce7;
        color: #15803d;
        border-color: #86efac;
    }

    /* Riwayat pengajuan */
    .history-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
        overflow: hidden;
    }
    .history-card .card-header {
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        padding: 18px 22px;
    }
    .history-card .card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #212529;
    }
    .history-card .card-body {
        padding: 0;
    }
    .history-table {
        margin-bottom: 0;
    }
    .history-table thead th {
        background: #f8f9fa;
        color: #495057;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: 1px solid #dee2e6;
        padding: 14px 16px;
    }
    .history-table tbody td {
        vertical-align: middle;
        font-size: 13px;
        padding: 15px 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    .history-table tbody tr:last-child td {
        border-bottom: none;
    }
    .history-table tbody tr:hover {
        background-color: #fafafa;
    }
    .vendor-name {
        font-weight: 600;
        color: #212529;
    }
    .nominal {
        display: block;
        margin-top: 3px;
        font-weight: 700;
        color: #198754;
    }
    .divisi-name {
        color: #6c757d;
        font-size: 12px;
    }
    .btn-detail {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        padding: 7px 11px;
    }
    .empty-history {
        padding: 45px 20px !important;
        text-align: center;
        color: #6c757d;
    }
    .empty-history i {
        font-size: 32px;
        margin-bottom: 10px;
        display: block;
        color: #adb5bd;
    }
    .detail-label {
        width: 35%;
        font-weight: 600;
        color: #495057;
        background: #f8f9fa;
    }
    .detail-table td {
        padding: 11px 14px;
        font-size: 13px;
        border-color: #e9ecef;
    }
    .link-berkas {
        display: inline-block;
        text-decoration: none;
        background: #e9f2ff;
        color: #0d6efd;
        padding: 6px 10px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        margin: 2px;
    }
    .link-berkas:hover {
        background: #dceaff;
        color: #0a58ca;
    }

    /* Style menarik untuk bagian instruksi menu */
    .menu-instruksi {
        margin-top: 30px;
        padding: 25px;
        background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .menu-instruksi:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    .menu-instruksi h5 {
        font-weight: 700;
        margin-bottom: 15px;
        color: #006064;
        font-size: 1.2rem;
    }
    .menu-instruksi ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .menu-instruksi li {
        margin-bottom: 10px;
        padding-left: 20px;
        position: relative;
        font-size: 14px;
        color: #004d40;
    }
    .menu-instruksi li::before {
        content: "➤";
        position: absolute;
        left: 0;
        color: #006064;
        font-size: 1.2em;
    }

    @media(max-width: 768px){
        .history-table { min-width: 760px; }
        .menu-instruksi {
            padding: 20px;
        }
        .menu-instruksi h5 {
            font-size: 1.1rem;
        }
        .btn-serapan-detail {
            width: 100%;
            text-align: center;
        }
    }
</style>

<!-- Welcome Box -->
<div class="welcome-box">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-2">Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>!</h3>
            <p class="mb-0" style="opacity: 0.85; font-size: 0.95rem;">Berikut ringkasan aktivitas persetujuan dana Anda sebagai Direktur Keuangan.</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Tanggal</span>
            <h5 class="mb-0 fw-bold mt-1"><?php echo date('d F Y'); ?></h5>
        </div>
    </div>
</div>

<!-- Statistik -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-title">Menunggu Persetujuan Final</div>
                <div class="stat-number"><?php echo $total_pending; ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="stat-title">Riwayat Disetujui (ACC Final)</div>
                <div class="stat-number"><?php echo $total_disetujui; ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body">
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="stat-title">Riwayat Ditolak</div>
                <div class="stat-number"><?php echo $total_ditolak; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Ringkas Serapan Anggaran (full width, di atas) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card history-card">
            <div class="card-header">
                <h5><i class="fas fa-chart-column text-primary me-2"></i> Serapan Anggaran</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="serapan-icon"><i class="fas fa-sack-dollar"></i></div>
                        <div>
                            <span class="serapan-title d-block">Total Realisasi Bulan Ini</span>
                            <h3 class="serapan-number">Rp <?php echo number_format($total_realisasi_bulan_ini, 0, ',', '.'); ?></h3>
                            <span class="serapan-sub"><?php echo $jumlah_transaksi_bulan_ini; ?> transaksi selesai divalidasi</span>
                        </div>
                    </div>
                    <a href="serapan_anggaran.php" class="btn-serapan-detail">
                        <i class="fas fa-chart-pie me-1"></i> Lihat Detail Serapan Anggaran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Prioritas: Menunggu Keputusan Anda (full width, di bawah) -->
<div class="card history-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-clipboard-list text-warning me-2"></i> Menunggu Keputusan Anda</h5>
        <?php if ($total_pending > 5) { ?>
            <a href="persetujuan.php" class="btn btn-sm btn-light border fw-semibold text-muted">Lihat Semua (<?php echo $total_pending; ?>)</a>
        <?php } ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table history-table mb-0">
                <thead>
                    <tr>
                        <th>Deadline</th>
                        <th>Divisi & SPV</th>
                        <th>Vendor & Nominal</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_prioritas) > 0) {
                        while ($row = mysqli_fetch_assoc($result_prioritas)) {
                            $nominal_prioritas = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                            $tgl_tempo_prioritas = date('d F Y', strtotime($row['tanggal_jatuh_tempo']));
                    ?>
                    <tr>
                        <td class="text-danger fw-bold"><i class="far fa-calendar-alt me-1"></i> <?php echo $tgl_tempo_prioritas; ?></td>
                        <td>
                            <span class="vendor-name d-block"><?php echo htmlspecialchars($row['nama_spv']); ?></span>
                            <span class="divisi-name"><?php echo htmlspecialchars($row['nama_divisi'] ?? '-'); ?></span>
                        </td>
                        <td>
                            <span class="vendor-name d-block"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                            <span class="nominal"><?php echo $nominal_prioritas; ?></span>
                        </td>
                        <td class="text-center">
                            <a href="persetujuan.php" class="btn btn-sm btn-outline-success btn-detail"><i class="fas fa-pen-to-square me-1"></i> Proses</a>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" class="empty-history"><i class="fas fa-mug-hot"></i> Tidak ada tugas mendesak saat ini.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bagian menu instruksi -->
<div class="menu-instruksi">
    <h5>Instruksi Menu</h5>
    <ul>
        <li>Persetujuan Final: Tinjau dan konfirmasi pengajuan dana yang sudah disetujui final.</li>
        <li>Riwayat Persetujuan: Lihat riwayat pengajuan dan persetujuan yang sudah selesai.</li>
        <li>Serapan Anggaran: Pantau realisasi dana yang sudah selesai divalidasi per divisi dan per periode.</li>
    </ul>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalDetailLabel">
                    <i class="fas fa-file-invoice me-2 text-success"></i>
                    Rincian Pengajuan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered detail-table mb-0">
                        <tbody>
                            <tr>
                                <td class="detail-label">Tanggal Dibuat</td>
                                <td id="det_tgl"></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Diajukan Oleh</td>
                                <td id="det_spv"></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Nama Vendor</td>
                                <td id="det_vendor"></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Nominal Pengajuan</td>
                                <td id="det_nominal" class="fw-bold text-success"></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Keterangan Awal</td>
                                <td id="det_keterangan"></td>
                            </tr>
                            <tr>
                                <td class="detail-label">File Penawaran</td>
                                <td id="det_file"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-center fw-bold bg-light">
                                    <i class="fas fa-clipboard-check me-1"></i>
                                    STATUS LAPORAN (JIKA ADA)
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label">Bukti Nota Akhir</td>
                                <td id="det_nota"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function bukaModalDetail(btn) {
        document.getElementById('det_tgl').innerText =
            btn.getAttribute('data-tgl') || '-';
        document.getElementById('det_spv').innerText =
            btn.getAttribute('data-spv') || '-';
        document.getElementById('det_vendor').innerText =
            btn.getAttribute('data-vendor') || '-';
        document.getElementById('det_nominal').innerText =
            btn.getAttribute('data-nominal') || '-';
        document.getElementById('det_keterangan').innerText =
            btn.getAttribute('data-keterangan') || '-';

        const filePenawaran = btn.getAttribute('data-file');
        if (filePenawaran) {
            document.getElementById('det_file').innerHTML =
                `<a href="../uploads/penawaran/${encodeURIComponent(filePenawaran)}" target="_blank" class="link-berkas"><i class="fas fa-file-pdf me-1"></i> Lihat File</a>`;
        } else {
            document.getElementById('det_file').innerHTML =
                '<span class="text-muted">Tidak ada file</span>';
        }

        const fileNota = btn.getAttribute('data-nota');
        if (fileNota) {
            const notaArray = fileNota.split(',');
            let notaHtml = '';
            notaArray.forEach(function(nota, index) {
                nota = nota.trim();
                if (nota) {
                    notaHtml += `<a href="../uploads/nota/${encodeURIComponent(nota)}" target="_blank" class="link-berkas"><i class="fas fa-file-lines me-1"></i> Nota ${index + 1}</a>`;
                }
            });
            document.getElementById('det_nota').innerHTML = notaHtml || '<span class="text-muted">Belum ada laporan</span>';
        } else {
            document.getElementById('det_nota').innerHTML = '<span class="text-muted">Belum ada laporan</span>';
        }

        const modalElement = document.getElementById('modalDetail');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    }
</script>

<?php require 'footer.php'; ?>