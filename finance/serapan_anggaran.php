<?php
session_start();
require '../config/koneksi.php';

// Halaman ini dipakai bersama oleh 3 role: Finance, Dirut, dan Dirkeu.
// Datanya company-wide (bukan spesifik per-user), jadi cukup 1 kode yang sama
// disalin ke folder masing-masing role (masing-masing tetap pakai header.php/footer.php sendiri).
$role_diizinkan = ['finance', 'dirut', 'dirkeu'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $role_diizinkan)) {
    header("Location: ../index.php");
    exit();
}

$nama_bulan_indo = ['01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];

// =========================================================================
// LOGIKA FILTER
// =========================================================================
$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : '';
$filter_divisi = isset($_GET['filter_divisi']) ? (int) $_GET['filter_divisi'] : 0;

// Dasar semua query: hanya pengajuan yang laporannya sudah SELESAI/VALID
// (bukan sekadar disetujui — harus sudah divalidasi Finance dengan realisasi dana tercatat)
$where_filter = " WHERE p.status_laporan = 'Valid' ";

if (!empty($filter_bulan)) {
    $bulan_aman = mysqli_real_escape_string($koneksi, $filter_bulan);
    $where_filter .= " AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$bulan_aman' ";
}
if ($filter_divisi > 0) {
    $where_filter .= " AND d.id = $filter_divisi ";
}

$parameter_url = "";
if (!empty($filter_bulan)) $parameter_url .= "&filter_bulan=$filter_bulan";
if ($filter_divisi > 0) $parameter_url .= "&filter_divisi=$filter_divisi";

// =========================================================================
// DROPDOWN FILTER: Bulan & Divisi (hanya yang punya transaksi selesai)
// =========================================================================
$query_dropdown_bulan = "SELECT DISTINCT DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') AS val_bulan 
                         FROM pengajuan p
                         WHERE p.status_laporan = 'Valid'
                         ORDER BY val_bulan DESC";
$result_dropdown_bulan = mysqli_query($koneksi, $query_dropdown_bulan);

$query_dropdown_divisi = "SELECT DISTINCT d.id, d.nama_divisi 
                          FROM pengajuan p
                          JOIN users u ON p.user_id = u.id
                          JOIN divisi d ON u.divisi_id = d.id
                          WHERE p.status_laporan = 'Valid'
                          ORDER BY d.nama_divisi ASC";
$result_dropdown_divisi = mysqli_query($koneksi, $query_dropdown_divisi);

// =========================================================================
// KARTU RINGKASAN
// =========================================================================
$query_ringkasan = "SELECT 
                        COUNT(p.id) AS jumlah_transaksi,
                        SUM(p.realisasi_dana) AS total_realisasi,
                        AVG(p.realisasi_dana) AS rata_realisasi,
                        SUM(p.biaya_diajukan) AS total_diajukan
                     FROM pengajuan p
                     JOIN users u ON p.user_id = u.id
                     LEFT JOIN divisi d ON u.divisi_id = d.id
                     $where_filter";
$result_ringkasan = mysqli_query($koneksi, $query_ringkasan);
$ringkasan = mysqli_fetch_assoc($result_ringkasan);

$jumlah_transaksi   = $ringkasan['jumlah_transaksi'] ?? 0;
$total_realisasi    = $ringkasan['total_realisasi'] ?? 0;
$rata_realisasi     = $ringkasan['rata_realisasi'] ?? 0;
$total_diajukan     = $ringkasan['total_diajukan'] ?? 0;
$total_efisiensi    = $total_diajukan - $total_realisasi; // positif = hemat, negatif = lebih dari yang diajukan

// =========================================================================
// DATA GRAFIK: Realisasi per Divisi
// =========================================================================
$query_grafik = "SELECT d.nama_divisi, SUM(p.realisasi_dana) AS total_realisasi
                 FROM pengajuan p
                 JOIN users u ON p.user_id = u.id
                 JOIN divisi d ON u.divisi_id = d.id
                 $where_filter
                 GROUP BY d.id
                 ORDER BY total_realisasi DESC";
$result_grafik = mysqli_query($koneksi, $query_grafik);

$label_divisi = [];
$data_realisasi = [];
if (mysqli_num_rows($result_grafik) > 0) {
    while ($row = mysqli_fetch_assoc($result_grafik)) {
        if ($row['total_realisasi'] === null) continue;
        $label_divisi[] = $row['nama_divisi'];
        $data_realisasi[] = (float) $row['total_realisasi'];
    }
}

// =========================================================================
// TABEL RINCIAN TRANSAKSI (Pagination)
// =========================================================================
$limit = 10;
$halaman_aktif = isset($_GET['halaman']) ? (int) $_GET['halaman'] : 1;
$offset = ($halaman_aktif - 1) * $limit;

$query_count = "SELECT COUNT(p.id) AS total_data 
                FROM pengajuan p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN divisi d ON u.divisi_id = d.id
                $where_filter";
$result_count = mysqli_query($koneksi, $query_count);
$total_data = mysqli_fetch_assoc($result_count)['total_data'] ?? 0;
$total_halaman = ceil($total_data / $limit);

$query_tabel = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi
                FROM pengajuan p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN divisi d ON u.divisi_id = d.id
                $where_filter
                ORDER BY p.tanggal_dibuat DESC
                LIMIT $limit OFFSET $offset";
$result_tabel = mysqli_query($koneksi, $query_tabel);

require 'header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Kartu Ringkasan */
    .sa-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.3s ease;
        animation: slideUp 0.4s ease forwards;
        opacity: 0;
        height: 100%;
    }
    .sa-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

    .sa-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .sa-info p {
        margin: 0 0 4px 0;
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sa-info h3 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.2;
    }

    .sa-total .sa-icon { background: #dcfce7; color: var(--lkpi-green); }
    .sa-total h3 { color: var(--lkpi-green); }

    .sa-jumlah .sa-icon { background: #eff6ff; color: #1d4ed8; }
    .sa-rata .sa-icon { background: #f3e8ff; color: #7e22ce; }

    .sa-efisiensi-hemat .sa-icon { background: #ecfdf5; color: #059669; }
    .sa-efisiensi-hemat h3 { color: #059669; }
    .sa-efisiensi-lebih .sa-icon { background: #fef2f2; color: #dc2626; }
    .sa-efisiensi-lebih h3 { color: #dc2626; }

    /* Panel Box (Filter, Grafik, Tabel) */
    .panel-box {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 25px;
        margin-bottom: 25px;
        animation: slideUp 0.4s ease forwards;
        opacity: 0;
    }
    .panel-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Filter Box */
    .filter-wrapper {
        background: #f8fafc;
        padding: 15px 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }

    /* Tabel */
    .table-modern { margin-bottom: 0; }
    .table-modern thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-modern tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        font-size: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .table-modern tbody tr:hover { background-color: #f8fafc; }

    .vendor-name { font-weight: 600; color: #1e293b; font-size: 0.9rem; display: block; margin-bottom: 2px; }
    .nominal-text { color: var(--lkpi-green); font-weight: 700; font-size: 0.85rem; }
    .nominal-diajukan { color: #94a3b8; font-size: 0.75rem; text-decoration: line-through; }

    .badge-soft {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .badge-efisien-hemat { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-efisien-lebih { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-efisien-sama { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    .btn-detail-sa {
        background: #ffffff;
        color: #0ea5e9;
        border: 1px solid #bae6fd;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: 0.2s;
    }
    .btn-detail-sa:hover { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }

    .pagination { margin-bottom: 0; justify-content: flex-end; }
    .page-link { color: var(--text-main); border: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; margin: 0 2px; }
    .page-item.active .page-link { background-color: var(--lkpi-green); color: white; }
    .page-link:hover:not(.active) { background-color: #f1f5f9; color: var(--lkpi-green); }

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
        margin-right: 5px;
        margin-bottom: 5px;
    }
    .link-berkas:hover { background: #e2e8f0; color: #007031; }
</style>

<div class="mb-4">
    <h4 class="fw-bold mb-1" style="color: var(--text-main);"><i class="fas fa-chart-pie text-primary me-2"></i> Serapan Anggaran</h4>
    <p class="text-muted mb-0" style="font-size: 0.875rem;">Ringkasan realisasi dana dari seluruh pengajuan yang laporannya sudah selesai divalidasi (Valid).</p>
</div>

<!-- Kartu Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sa-card sa-total" style="animation-delay: 0.1s;">
            <div class="sa-icon"><i class="fas fa-sack-dollar"></i></div>
            <div class="sa-info">
                <p>Total Realisasi</p>
                <h3>Rp <?php echo number_format($total_realisasi, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sa-card sa-jumlah" style="animation-delay: 0.2s;">
            <div class="sa-icon"><i class="fas fa-file-circle-check"></i></div>
            <div class="sa-info">
                <p>Transaksi Selesai</p>
                <h3><?php echo number_format($jumlah_transaksi, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sa-card sa-rata" style="animation-delay: 0.3s;">
            <div class="sa-icon"><i class="fas fa-calculator"></i></div>
            <div class="sa-info">
                <p>Rata-rata / Transaksi</p>
                <h3>Rp <?php echo number_format($rata_realisasi, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <?php $kelas_efisiensi = $total_efisiensi >= 0 ? 'sa-efisiensi-hemat' : 'sa-efisiensi-lebih'; ?>
        <div class="sa-card <?php echo $kelas_efisiensi; ?>" style="animation-delay: 0.4s;">
            <div class="sa-icon"><i class="fas fa-<?php echo $total_efisiensi >= 0 ? 'arrow-trend-down' : 'arrow-trend-up'; ?>"></i></div>
            <div class="sa-info">
                <p><?php echo $total_efisiensi >= 0 ? 'Total Hemat' : 'Total Lebih dari Diajukan'; ?></p>
                <h3>Rp <?php echo number_format(abs($total_efisiensi), 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Grafik -->
    <div class="col-12 col-xl-5">
        <div class="panel-box h-100" style="animation-delay: 0.1s;">
            <h5 class="panel-title"><i class="fas fa-chart-column text-primary"></i> Realisasi per Divisi</h5>
            <p class="text-muted mb-3" style="font-size: 0.8rem;">Berdasarkan filter yang dipilih di bawah.</p>

            <div style="position: relative; height: 300px;">
                <?php if (empty($data_realisasi)) { ?>
                    <div class="d-flex h-100 justify-content-center align-items-center flex-column text-muted bg-light rounded-3" style="border: 1px dashed #cbd5e1;">
                        <i class="fas fa-chart-pie fa-2x mb-2 opacity-50"></i>
                        <span style="font-size: 0.85rem;">Tidak ada data pada filter ini.</span>
                    </div>
                <?php } else { ?>
                    <canvas id="chartSerapanAnggaran"></canvas>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Filter + Tabel -->
    <div class="col-12 col-xl-7">
        <div class="panel-box" style="animation-delay: 0.2s;">
            <h5 class="panel-title mb-3"><i class="fas fa-list-ul text-warning"></i> Rincian Transaksi Selesai</h5>

            <!-- Filter -->
            <div class="filter-wrapper">
                <form action="" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-auto">
                        <span class="text-muted fw-bold" style="font-size: 0.8rem;"><i class="fas fa-filter me-1"></i> Filter:</span>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="filter_bulan" class="form-select form-select-sm">
                            <option value="">Semua Bulan</option>
                            <?php
                            if (mysqli_num_rows($result_dropdown_bulan) > 0) {
                                while ($row_bln = mysqli_fetch_assoc($result_dropdown_bulan)) {
                                    $val_bulan = $row_bln['val_bulan'];
                                    $pecah = explode('-', $val_bulan);
                                    $label_tampil = ($nama_bulan_indo[$pecah[1]] ?? $pecah[1]) . " " . $pecah[0];
                                    $selected = ($filter_bulan == $val_bulan) ? 'selected' : '';
                                    echo "<option value='$val_bulan' $selected>$label_tampil</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="filter_divisi" class="form-select form-select-sm">
                            <option value="0">Semua Divisi</option>
                            <?php
                            if (mysqli_num_rows($result_dropdown_divisi) > 0) {
                                while ($row_dv = mysqli_fetch_assoc($result_dropdown_divisi)) {
                                    $selected = ($filter_divisi == $row_dv['id']) ? 'selected' : '';
                                    echo "<option value='" . $row_dv['id'] . "' $selected>" . htmlspecialchars($row_dv['nama_divisi']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold">Terapkan</button>
                        <a href="serapan_anggaran.php" class="btn btn-sm btn-light border px-3 rounded-2 fw-semibold text-muted">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Tgl</th>
                            <th>Divisi & SPV</th>
                            <th>Vendor</th>
                            <th>Realisasi</th>
                            <th class="text-center">Efisiensi</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result_tabel) > 0) {
                            while ($row = mysqli_fetch_assoc($result_tabel)) {
                                $tgl = date('d M Y', strtotime($row['tanggal_dibuat']));
                                $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';
                                $diajukan = (float) $row['biaya_diajukan'];
                                $realisasi = isset($row['realisasi_dana']) ? (float) $row['realisasi_dana'] : null;
                                $selisih = $realisasi !== null ? ($diajukan - $realisasi) : null;

                                if ($selisih === null) {
                                    $badge_efisiensi = '<span class="badge-soft">-</span>';
                                } elseif ($selisih > 0) {
                                    $badge_efisiensi = '<span class="badge-soft badge-efisien-hemat"><i class="fas fa-arrow-down me-1"></i> Rp ' . number_format($selisih, 0, ',', '.') . '</span>';
                                } elseif ($selisih < 0) {
                                    $badge_efisiensi = '<span class="badge-soft badge-efisien-lebih"><i class="fas fa-arrow-up me-1"></i> Rp ' . number_format(abs($selisih), 0, ',', '.') . '</span>';
                                } else {
                                    $badge_efisiensi = '<span class="badge-soft badge-efisien-sama">Sama</span>';
                                }
                        ?>
                        <tr>
                            <td class="text-dark"><?php echo $tgl; ?></td>
                            <td>
                                <strong class="d-block text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['nama_spv']); ?></strong>
                                <span class="text-muted" style="font-size: 0.72rem;"><?php echo htmlspecialchars($nama_divisi); ?></span>
                            </td>
                            <td>
                                <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                            </td>
                            <td>
                                <div class="nominal-text"><?php echo $realisasi !== null ? 'Rp ' . number_format($realisasi, 0, ',', '.') : '-'; ?></div>
                                <div class="nominal-diajukan">Diajukan: Rp <?php echo number_format($diajukan, 0, ',', '.'); ?></div>
                            </td>
                            <td class="text-center"><?php echo $badge_efisiensi; ?></td>
                            <td class="text-center">
                                <button type="button" class="btn-detail-sa"
                                    data-bs-toggle="modal" data-bs-target="#modalDetailSA"
                                    onclick="bukaModalDetailSA(this)"
                                    data-tgl="<?php echo date('d F Y', strtotime($row['tanggal_dibuat'])); ?>"
                                    data-spv="<?php echo htmlspecialchars($row['nama_spv']); ?> (<?php echo htmlspecialchars($nama_divisi); ?>)"
                                    data-vendor="<?php echo htmlspecialchars($row['nama_vendor']); ?>"
                                    data-keterangan="<?php echo htmlspecialchars($row['keterangan'] ?? ''); ?>"
                                    data-diajukan="<?php echo $diajukan; ?>"
                                    data-realisasi="<?php echo $realisasi !== null ? $realisasi : ''; ?>"
                                    data-rincian="<?php echo htmlspecialchars($row['rincian_pembelian'] ?? ''); ?>"
                                    data-nota="<?php echo htmlspecialchars($row['bukti_nota'] ?? ''); ?>"
                                ><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'><i class='fas fa-inbox fa-2x mb-3 d-block opacity-25'></i> Belum ada transaksi selesai pada filter ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman > 1): ?>
            <div class="pt-3">
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
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetailSA" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-receipt text-primary me-2"></i> Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px;" class="d-flex justify-content-between">
                    <div>
                        <span class="d-block text-muted" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Dana Diajukan</span>
                        <strong id="det_diajukan" class="text-dark"></strong>
                    </div>
                    <div class="text-end">
                        <span class="d-block text-muted" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Realisasi</span>
                        <strong id="det_realisasi_sa" style="color: var(--lkpi-green);"></strong>
                    </div>
                </div>

                <table class="table table-borderless table-sm mb-2" style="font-size: 0.875rem;">
                    <tr><td width="35%" class="text-muted fw-medium">Tanggal</td><td width="5%">:</td><td id="det_tgl_sa" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">SPV & Divisi</td><td>:</td><td id="det_spv_sa" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Vendor</td><td>:</td><td id="det_vendor_sa" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Keterangan</td><td class="align-top">:</td><td id="det_keterangan_sa" class="text-dark"></td></tr>
                </table>

                <div class="mb-2">
                    <span class="text-muted d-block mb-1" style="font-size: 0.75rem; font-weight: 600;">Rincian Pembelian</span>
                    <div id="det_rincian_sa" style="white-space: pre-wrap; font-family: monospace; font-size:0.75rem; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1;"></div>
                </div>

                <div>
                    <span class="text-muted d-block mb-1" style="font-size: 0.75rem; font-weight: 600;">Bukti Nota</span>
                    <div id="det_nota_sa"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function bukaModalDetailSA(btn) {
        document.getElementById('det_tgl_sa').innerText = btn.getAttribute('data-tgl');
        document.getElementById('det_spv_sa').innerText = btn.getAttribute('data-spv');
        document.getElementById('det_vendor_sa').innerText = btn.getAttribute('data-vendor');
        document.getElementById('det_keterangan_sa').innerText = btn.getAttribute('data-keterangan') || '-';

        let diajukan = parseFloat(btn.getAttribute('data-diajukan')) || 0;
        document.getElementById('det_diajukan').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(diajukan);

        let realisasi = btn.getAttribute('data-realisasi');
        document.getElementById('det_realisasi_sa').innerText = (realisasi && realisasi.trim() !== '') ? 'Rp ' + new Intl.NumberFormat('id-ID').format(parseFloat(realisasi)) : '-';

        let rincian = btn.getAttribute('data-rincian');
        document.getElementById('det_rincian_sa').innerText = rincian && rincian.trim() !== '' ? rincian : 'Tidak ada rincian.';

        let fileNota = btn.getAttribute('data-nota');
        if (fileNota && fileNota.trim() !== '') {
            let notaArray = fileNota.split(',');
            let notaHtml = '';
            notaArray.forEach((nota, index) => {
                let trimmed = nota.trim();
                if (trimmed !== '') {
                    notaHtml += `<a href="../uploads/nota/${encodeURIComponent(trimmed)}" target="_blank" class="link-berkas"><i class="fas fa-file-invoice"></i> Nota ${index + 1}</a>`;
                }
            });
            document.getElementById('det_nota_sa').innerHTML = notaHtml !== '' ? notaHtml : '<span class="text-muted fst-italic" style="font-size:0.8rem;">Tidak ada nota</span>';
        } else {
            document.getElementById('det_nota_sa').innerHTML = '<span class="text-muted fst-italic" style="font-size:0.8rem;">Tidak ada nota</span>';
        }
    }
</script>

<?php if (!empty($data_realisasi)) { ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const labels = <?php echo json_encode($label_divisi); ?>;
        const dataVal = <?php echo json_encode($data_realisasi); ?>;

        const ctx = document.getElementById('chartSerapanAnggaran').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Realisasi Dana (Rp)',
                    data: dataVal,
                    backgroundColor: [
                        'rgba(0, 138, 61, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let value = Number(context.raw) || 0;
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                let numValue = Number(value);
                                if (numValue >= 1000000000) return 'Rp ' + (numValue / 1000000000) + ' M';
                                if (numValue >= 1000000) return 'Rp ' + (numValue / 1000000) + ' Jt';
                                return 'Rp ' + numValue.toLocaleString('id-ID');
                            }
                        },
                        grid: { borderDash: [4, 4] }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    });
</script>
<?php } ?>

<?php require 'footer.php'; ?>