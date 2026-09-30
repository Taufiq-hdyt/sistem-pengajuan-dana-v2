<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah Dirut
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dirut') {
    header("Location: ../index.php");
    exit();
}

// --- LOGIKA FILTER PENCARIAN ---
// Hanya tampilkan yang sudah diproses oleh Dirut (Disetujui atau Ditolak)
// Catatan: Dirut hanya bisa Menyetujui/Menolak, tidak bisa meminta Revisi (itu wewenang Manager & Finance saja)
$where_clause = "WHERE p.status_dirut != 'Pending'";

// 1. Filter Bulan
$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : '';
if (!empty($filter_bulan)) {
    $bulan_aman = mysqli_real_escape_string($koneksi, $filter_bulan);
    $where_clause .= " AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$bulan_aman'";
}

// 2. Filter Status Dirut
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
if (!empty($filter_status)) {
    $status_aman = mysqli_real_escape_string($koneksi, $filter_status);
    $where_clause .= " AND p.status_dirut = '$status_aman'";
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
                         WHERE status_dirut != 'Pending' 
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

    .form-select-sm { border-radius: 8px; font-weight: 500; color: #475569; }

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
    .nominal-text { color: var(--lkpi-green); font-weight: 700; font-size: 0.95rem; }
    
    /* Smart Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-setuju { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-tolak { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .alasan-ringkas {
        font-size: 0.7rem;
        line-height: 1.2;
        max-width: 220px;
        color: #64748b;
        display: block;
        margin-top: 4px;
    }

    /* Badge Status Akhir - pill kecil dengan ikon, senada dengan badge Keputusan di atasnya */
    .status-akhir-badge {
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
    }
    .akhir-selesai { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .akhir-cair { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .akhir-batal { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .akhir-menunggu { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .akhir-netral { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    .realisasi-text {
        font-size: 0.7rem;
        color: #64748b;
        display: block;
        margin-top: 4px;
    }

    /* Tombol Detail */
    .btn-detail {
        background: #ffffff;
        color: #0ea5e9;
        border: 1px solid #bae6fd;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: 0.2s;
    }
    .btn-detail:hover { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }

    /* Pagination */
    .pagination { margin-bottom: 0; justify-content: flex-end; }
    .page-link { color: var(--text-main); border: none; font-size: 0.875rem; font-weight: 500; border-radius: 8px; margin: 0 2px; }
    .page-item.active .page-link { background-color: var(--lkpi-green); color: white; }
    .page-link:hover:not(.active) { background-color: #f1f5f9; color: var(--lkpi-green); }
</style>

<div class="card-clean">
    <div class="card-header-clean d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title fw-bold m-0"><i class="fas fa-clock-rotate-left text-secondary me-2"></i> Histori Keputusan Anda</h5>
        <span class="text-muted" style="font-size: 0.8rem;">Menampilkan <b><?php echo ($total_data > 0) ? $offset + 1 : 0; ?> - <?php echo min($offset + $limit, $total_data); ?></b> dari <b><?php echo $total_data; ?></b> Dokumen</span>
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
                    <option value="">Semua Keputusan</option>
                    <option value="Disetujui" <?php if($filter_status == 'Disetujui') echo 'selected'; ?>>✅ Disetujui</option>
                    <option value="Ditolak" <?php if($filter_status == 'Ditolak') echo 'selected'; ?>>❌ Ditolak</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold">Cari</button>
                <a href="riwayat_keputusan.php" class="btn btn-sm btn-light border px-3 rounded-2 fw-semibold text-muted">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th class="ps-4">Tgl Dibuat</th>
                    <th>Divisi & SPV</th>
                    <th>Vendor & Nominal</th>
                    <th>Keputusan Anda</th>
                    <th class="text-center pe-4">Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result_riwayat) > 0) {
                    while ($row = mysqli_fetch_assoc($result_riwayat)) { 
                        $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                        $tgl = date('d M Y', strtotime($row['tanggal_dibuat']));
                        $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';
                        $stat_dirut = $row['status_dirut'];
                        $stat_dirkeu = $row['status_dirkeu'] ?? 'Pending';
                        $catatan = htmlspecialchars($row['catatan_revisi'] ?? '');
                        
                        // ------------------------------------------------------------
                        // Badge Keputusan Dirut
                        // Dirut hanya punya 2 kemungkinan keputusan: Disetujui / Ditolak
                        // (permintaan Revisi adalah wewenang Manager & Finance saja,
                        //  jadi status_dirut tidak pernah bernilai 'Revisi')
                        // ------------------------------------------------------------
                        if ($stat_dirut == 'Disetujui') {
                            $badge_keputusan = '<span class="status-badge badge-setuju"><i class="fas fa-check"></i> Disetujui</span>';
                        } elseif ($stat_dirut == 'Ditolak') {
                            $badge_keputusan = '<span class="status-badge badge-tolak"><i class="fas fa-xmark"></i> Ditolak</span>';
                        } else {
                            // Jaga-jaga kalau ada nilai status lain yang tidak terduga di database
                            $badge_keputusan = '<span class="status-badge akhir-netral">'.htmlspecialchars($stat_dirut).'</span>';
                        }

                        // Tampilkan ringkasan alasan di bawah badge, kalau keputusannya Ditolak
                        if ($stat_dirut == 'Ditolak' && !empty($catatan)) {
                            $badge_keputusan .= '<span class="alasan-ringkas">Alasan: '.$catatan.'</span>';
                        }

                        // ------------------------------------------------------------
                        // Badge Status Akhir (Keseluruhan) untuk memberi konteks
                        //
                        // PENTING: cek status_laporan == 'Valid' PALING AWAL.
                        // status_keseluruhan tetap bernilai 'Disetujui' selamanya sejak
                        // Dirkeu approve, jadi kalau dicek duluan, statusnya akan selalu
                        // "nyangkut" di label "Cair" walau laporan sudah selesai divalidasi.
                        // ------------------------------------------------------------
                        if ($row['status_laporan'] == 'Valid') {
                            $badge_akhir = '<span class="status-akhir-badge akhir-selesai"><i class="fas fa-check-double"></i> Status Akhir: Selesai (Valid)</span>';
                            if (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) {
                                $badge_akhir .= '<span class="realisasi-text">Realisasi: Rp ' . number_format($row['realisasi_dana'], 0, ',', '.') . '</span>';
                            }
                        } elseif ($row['status_keseluruhan'] == 'Ditolak') {
                            $badge_akhir = '<span class="status-akhir-badge akhir-batal"><i class="fas fa-ban"></i> Status Akhir: Batal</span>';
                        } elseif ($row['status_keseluruhan'] == 'Disetujui') {
                            $badge_akhir = '<span class="status-akhir-badge akhir-cair"><i class="fas fa-sack-dollar"></i> Status Akhir: Cair, Menunggu Laporan</span>';
                        } elseif ($stat_dirut == 'Disetujui' && $stat_dirkeu != 'Disetujui' && $stat_dirkeu != 'Ditolak') {
                            // Dirut sudah setuju, tinggal menunggu keputusan akhir dari Dirkeu
                            $badge_akhir = '<span class="status-akhir-badge akhir-menunggu"><i class="fas fa-hourglass-half"></i> Status Akhir: Menunggu Dirkeu</span>';
                        } else {
                            $badge_akhir = '<span class="status-akhir-badge akhir-netral"><i class="fas fa-hourglass-half"></i> Status Akhir: Menunggu</span>';
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
                    <td>
                        <?php echo $badge_keputusan; ?><br>
                        <?php echo $badge_akhir; ?>
                    </td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn-detail" 
                            data-bs-toggle="modal" data-bs-target="#modalDetailRiwayat"
                            onclick="bukaModalRiwayat(this)"
                            data-tgl="<?php echo date('d F Y', strtotime($row['tanggal_dibuat'])); ?>"
                            data-spv="<?php echo htmlspecialchars($row['nama_spv']); ?> (<?php echo htmlspecialchars($row['nama_divisi']); ?>)"
                            data-vendor="<?php echo htmlspecialchars($row['nama_vendor']); ?>"
                            data-nominal="<?php echo $nominal; ?>"
                            data-keterangan="<?php echo htmlspecialchars($row['keterangan']); ?>"
                            data-rekening="<?php echo htmlspecialchars($row['rekening_tujuan']); ?>"
                            data-file="<?php echo htmlspecialchars($row['file_penawaran'] ?? ''); ?>"
                            data-keputusan="<?php echo htmlspecialchars($stat_dirut); ?>"
                            data-catatan="<?php echo $catatan; ?>"
                            data-realisasi="<?php echo (isset($row['realisasi_dana']) && $row['realisasi_dana'] !== null) ? $row['realisasi_dana'] : ''; ?>"
                        ><i class="fas fa-eye me-1"></i> Lihat Berkas</button>
                    </td>
                </tr>
                <?php 
                    } 
                } else { 
                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='fas fa-clock-rotate-left fa-3x mb-3 opacity-25 d-block'></i> Belum ada riwayat keputusan pada periode ini.</td></tr>"; 
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

<!-- Modal Detail Bootstrap 5 -->
<div class="modal fade" id="modalDetailRiwayat" tabindex="-1" aria-labelledby="modalDetailRiwayatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="modalDetailRiwayatLabel" style="color: var(--text-main);">
                    Arsip Keputusan Dirut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Nominal</span>
                    <h3 id="det_nominal" style="color: var(--text-main); margin: 0; font-weight: 800; letter-spacing: -0.5px;"></h3>
                    <div id="det_keputusan_badge" class="mt-2"></div>
                </div>

                <!-- Alasan Ditolak (muncul otomatis jika ada) -->
                <div id="det_alasan_wrapper" class="alert d-none mb-3" style="border-radius: 12px; border: 1px dashed #fca5a5; background-color: #fef2f2;">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-circle-xmark fs-5 me-2 mt-1 text-danger"></i>
                        <div>
                            <strong class="d-block mb-1 text-danger">Alasan Penolakan Anda:</strong>
                            <span id="det_alasan" class="text-dark" style="font-size: 0.875rem;"></span>
                        </div>
                    </div>
                </div>
                
                <table class="table table-borderless table-sm mb-0" style="font-size: 0.875rem;">
                    <tr><td width="35%" class="text-muted fw-medium">Tgl Dibuat</td><td width="5%">:</td><td id="det_tgl" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Diajukan Oleh</td><td>:</td><td id="det_spv" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Vendor</td><td>:</td><td id="det_vendor" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Keterangan</td><td class="align-top">:</td><td id="det_keterangan" class="text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Rekening</td><td class="align-top">:</td><td id="det_rekening" class="font-monospace text-dark bg-light p-1 rounded"></td></tr>
                    <tr><td class="text-muted fw-medium mt-2 pt-2 border-top">Lampiran</td><td class="mt-2 pt-2 border-top">:</td><td id="det_file" class="mt-2 pt-2 border-top"></td></tr>
                    <tr id="det_realisasi_wrapper" class="d-none"><td class="text-muted fw-medium">Realisasi Dana</td><td>:</td><td id="det_realisasi" class="fw-bold text-success"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function bukaModalRiwayat(btn) {
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
        
        // Logika warna badge status di dalam modal (2 kondisi: Disetujui / Ditolak)
        let keputusan = btn.getAttribute('data-keputusan');
        let badgeHtml = '';
        if (keputusan === 'Disetujui') {
            badgeHtml = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-check me-1"></i> Telah Anda Setujui</span>';
        } else {
            badgeHtml = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fas fa-xmark me-1"></i> Telah Anda Tolak</span>';
        }
        document.getElementById('det_keputusan_badge').innerHTML = badgeHtml;

        // Tampilkan alasan (catatan_revisi) hanya jika keputusan Ditolak dan ada catatannya
        let catatan = btn.getAttribute('data-catatan');
        let alasanWrapper = document.getElementById('det_alasan_wrapper');
        let alasanText = document.getElementById('det_alasan');

        if (keputusan === 'Ditolak' && catatan && catatan.trim() !== '') {
            alasanText.innerText = catatan;
            alasanWrapper.classList.remove('d-none');
        } else {
            alasanWrapper.classList.add('d-none');
        }

        let filePenawaran = btn.getAttribute('data-file');
        if (filePenawaran) {
            document.getElementById('det_file').innerHTML = '<a href="../uploads/penawaran/' + filePenawaran + '" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold"><i class="fas fa-file-pdf me-1"></i> Buka Penawaran</a>';
        } else {
            document.getElementById('det_file').innerHTML = '<span class="text-muted fst-italic">Tidak dilampirkan</span>';
        }
    }
</script>

<?php require 'footer.php'; ?>