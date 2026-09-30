<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah Finance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../index.php");
    exit();
}

// Proses jika tombol Setujui, Tolak, atau Revisi ditekan
if (isset($_POST['proses_persetujuan'])) {
    $id_pengajuan = $_POST['id_pengajuan'];
    $aksi = $_POST['aksi'];
    
    if ($aksi === 'setujui') {
        $query_update = "UPDATE pengajuan SET status_finance = 'Disetujui', catatan_revisi = NULL WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, "i", $id_pengajuan);
        $pesan = "Pengajuan berhasil disetujui dan diteruskan ke Direktur Utama.";
        $icon = "success";
    } elseif ($aksi === 'tolak') {
        $alasan = trim($_POST['alasan_penolakan']);
        $query_update = "UPDATE pengajuan SET status_finance = 'Ditolak', status_keseluruhan = 'Ditolak', catatan_revisi = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, "si", $alasan, $id_pengajuan);
        $pesan = "Pengajuan ditolak secara permanen.";
        $icon = "warning";
    } elseif ($aksi === 'revisi') {
        $catatan = trim($_POST['catatan_revisi']);
        // Mengubah status_finance & status_keseluruhan menjadi 'Revisi' secara tegas
        $query_update = "UPDATE pengajuan SET status_finance = 'Revisi', status_keseluruhan = 'Revisi', catatan_revisi = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, "si", $catatan, $id_pengajuan);
        $pesan = "Pengajuan dikembalikan ke SPV untuk direvisi.";
        $icon = "info";
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg'] = $pesan;
        $_SESSION['swal_icon'] = $icon;
    } else {
        $_SESSION['swal_msg'] = "Gagal memproses pengajuan. Terjadi kesalahan sistem.";
        $_SESSION['swal_icon'] = "error";
    }
    
    header("Location: persetujuan.php");
    exit();
}

// Filter diperketat: Hanya menampilkan data yang status_finance BENAR-BENAR 'Pending' 
// dan mengeluarkan status 'Revisi', 'Ditolak', 'Disetujui', maupun 'Selesai'
$query = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi 
          FROM pengajuan p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN divisi d ON u.divisi_id = d.id 
          WHERE LOWER(TRIM(p.status_manager)) = 'disetujui' 
          AND LOWER(TRIM(p.status_finance)) = 'pending'
          AND LOWER(TRIM(IFNULL(p.status_keseluruhan, ''))) NOT IN ('ditolak', 'disetujui', 'selesai', 'revisi')
          ORDER BY p.tanggal_jatuh_tempo ASC";
$result = mysqli_query($koneksi, $query);

require 'header.php';
?>

<style>
    .card-clean {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        animation: slideUp 0.4s ease forwards;
    }

    .card-header-clean {
        background: #ffffff;
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-main);
    }

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
    
    .badge-soft {
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.7rem;
        display: inline-flex;
        align-items: center;
        background-color: #f1f5f9; 
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .text-urgent {
        color: #dc2626; font-weight: 700; background: #fef2f2; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; display: inline-block; margin-top: 4px;
    }

    .btn-action-light {
        background: #ffffff; border: 1px solid #e2e8f0; color: #64748b; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; transition: 0.2s; width: 100%; margin-bottom: 8px;
    }
    .btn-action-light:hover { background: #f8fafc; border-color: #cbd5e1; color: #334155; }

    .action-group { display: flex; gap: 6px; }
    .btn-acc { flex: 1; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 6px 0; border-radius: 6px; font-size: 0.75rem; font-weight: 600; transition: 0.2s; text-align: center; }
    .btn-rev { flex: 1; background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 6px 0; border-radius: 6px; font-size: 0.75rem; font-weight: 600; transition: 0.2s; text-align: center; }
    .btn-rej { flex: 1; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 0; border-radius: 6px; font-size: 0.75rem; font-weight: 600; transition: 0.2s; text-align: center; }
</style>

<div class="card-clean">
    <div class="card-header-clean">
        <h5 class="card-title"><i class="fas fa-file-signature text-primary me-2"></i> Verifikasi Pengajuan (Finance)</h5>
    </div>
    
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th class="ps-4">Diajukan Oleh</th>
                    <th>Vendor & Keterangan</th>
                    <th>Nominal & Jatuh Tempo</th>
                    <th class="text-center pe-4" style="width: 180px;">Aksi Keputusan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                        $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';
                        $tgl_dibuat = date('d F Y', strtotime($row['tanggal_dibuat']));
                        
                        $tgl_tempo_obj = new DateTime($row['tanggal_jatuh_tempo']);
                        $hari_ini = new DateTime(date('Y-m-d'));
                        $selisih = $hari_ini->diff($tgl_tempo_obj);
                        
                        $is_terlewat = ($row['tanggal_jatuh_tempo'] < date('Y-m-d'));
                        $is_urgent = ($selisih->invert == 0 && $selisih->days <= 2 && !$is_terlewat);
                        $warna_teks_tempo = $is_terlewat ? '#dc2626' : '#64748b';
                ?>
                <tr>
                    <td class="ps-4">
                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['nama_spv']); ?></strong>
                        <span class="badge-soft mt-1"><?php echo htmlspecialchars($nama_divisi); ?></span>
                    </td>
                    <td>
                        <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                        <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($row['jenis_pengajuan']); ?></span>
                        <p class="mt-2 mb-0 text-muted" style="font-size: 0.8rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($row['keterangan']); ?>
                        </p>
                    </td>
                    <td>
                        <div class="nominal-text"><?php echo $nominal; ?></div>
                        <div class="mt-1" style="font-size: 0.75rem; color: <?php echo $warna_teks_tempo; ?>;">
                            <i class="far fa-calendar-alt me-1"></i>Tempo: <strong><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></strong>
                        </div>
                        <?php 
                        if ($is_terlewat) { echo '<span class="text-urgent"><i class="fas fa-exclamation-triangle me-1"></i>Terlewat!</span>'; } 
                        elseif ($is_urgent) { echo '<span class="text-urgent" style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a;"><i class="fas fa-clock me-1"></i>Mendesak</span>'; } 
                        ?>
                    </td>
                    <td class="pe-4">
                        <button type="button" class="btn-action-light" 
                            data-bs-toggle="modal" data-bs-target="#modalDetail"
                            onclick="isiModalDetail(this)"
                            data-tgl="<?php echo $tgl_dibuat; ?>"
                            data-spv="<?php echo htmlspecialchars($row['nama_spv']); ?> (<?php echo htmlspecialchars($nama_divisi); ?>)"
                            data-vendor="<?php echo htmlspecialchars($row['nama_vendor']); ?>"
                            data-jenis="<?php echo htmlspecialchars($row['jenis_pengajuan']); ?>"
                            data-nominal="<?php echo $nominal; ?>"
                            data-keterangan="<?php echo htmlspecialchars($row['keterangan']); ?>"
                            data-tempo="<?php echo date('d F Y', strtotime($row['tanggal_jatuh_tempo'])); ?>"
                            data-rekening="<?php echo htmlspecialchars($row['rekening_tujuan']); ?>"
                            data-file="<?php echo htmlspecialchars($row['file_penawaran']); ?>"
                        ><i class="fas fa-eye me-1"></i> Lihat Berkas</button>

                        <div class="action-group">
                            <form action="" method="POST" class="flex-grow-1" id="form-setuju-<?php echo $row['id']; ?>">
                                <input type="hidden" name="id_pengajuan" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="aksi" value="setujui">
                                <input type="hidden" name="proses_persetujuan" value="1">
                                <button type="button" onclick="konfirmasiSetuju(<?php echo $row['id']; ?>)" class="btn-acc w-100" title="Setujui"><i class="fas fa-check"></i></button>
                            </form>

                            <button type="button" class="btn-rev w-100" title="Minta Revisi" data-bs-toggle="modal" data-bs-target="#modalRevisi" onclick="siapkanRevisi(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_vendor'])); ?>')">
                                <i class="fas fa-rotate-left"></i>
                            </button>
                            
                            <button type="button" class="btn-rej w-100" title="Tolak Permanen" data-bs-toggle="modal" data-bs-target="#modalTolak" onclick="siapkanTolak(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_vendor'])); ?>')">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                    echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='fas fa-mug-hot fa-3x mb-3 opacity-25 d-block'></i> Tidak ada pengajuan baru yang menunggu verifikasi Anda.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Informasi Pengajuan (Finance)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Diajukan</span>
                    <h3 id="det_nominal" style="color: var(--lkpi-green); margin: 0; font-weight: 800;"></h3>
                </div>
                <table class="table table-borderless table-sm mb-0" style="font-size: 0.875rem;">
                    <tr><td width="35%" class="text-muted fw-medium">Tgl Dibuat</td><td width="5%">:</td><td id="det_tgl" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Diajukan Oleh</td><td>:</td><td id="det_spv" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Vendor</td><td>:</td><td id="det_vendor" class="fw-semibold text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Kategori</td><td>:</td><td><span id="det_jenis" class="badge bg-secondary bg-opacity-10 text-secondary border"></span></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Keterangan</td><td class="align-top">:</td><td id="det_keterangan" class="text-dark"></td></tr>
                    <tr><td class="text-muted fw-medium">Jatuh Tempo</td><td>:</td><td id="det_tempo" class="fw-semibold text-danger"></td></tr>
                    <tr><td class="text-muted fw-medium align-top">Rekening</td><td class="align-top">:</td><td id="det_rekening" class="font-monospace text-dark bg-light p-1 rounded"></td></tr>
                    <tr><td class="text-muted fw-medium mt-2 pt-2 border-top">Lampiran</td><td class="mt-2 pt-2 border-top">:</td><td id="det_file" class="mt-2 pt-2 border-top"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Revisi -->
<div class="modal fade" id="modalRevisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-warning" style="color: #d97706 !important;"><i class="fas fa-triangle-exclamation me-2"></i> Form Minta Revisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <p style="font-size: 0.85rem; color: #64748b;">Pengajuan: <strong id="namaVendorRevisi" class="text-dark"></strong></p>
                    <input type="hidden" name="id_pengajuan" id="idPengajuanRevisi">
                    <input type="hidden" name="aksi" value="revisi">
                    <input type="hidden" name="proses_persetujuan" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #334155;">Catatan Revisi untuk SPV:</label>
                        <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Contoh: Lampiran penawaran buram, tolong unggah ulang..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white px-4" style="background: #f59e0b;">Kirim Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-ban me-2"></i> Alasan Penolakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <p style="font-size: 0.85rem; color: #64748b;">Pengajuan: <strong id="namaVendorTolak" class="text-dark"></strong></p>
                    <input type="hidden" name="id_pengajuan" id="idPengajuanTolak">
                    <input type="hidden" name="aksi" value="tolak">
                    <input type="hidden" name="proses_persetujuan" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #334155;">Jelaskan Alasan Penolakan:</label>
                        <textarea name="alasan_penolakan" class="form-control" rows="4" required placeholder="Tulis alasan kenapa pengajuan ini ditolak dari sisi keuangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Tolak Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function isiModalDetail(btn) {
        document.getElementById('det_tgl').innerText = btn.getAttribute('data-tgl');
        document.getElementById('det_spv').innerText = btn.getAttribute('data-spv');
        document.getElementById('det_vendor').innerText = btn.getAttribute('data-vendor');
        document.getElementById('det_jenis').innerText = btn.getAttribute('data-jenis');
        document.getElementById('det_nominal').innerText = btn.getAttribute('data-nominal');
        document.getElementById('det_keterangan').innerText = btn.getAttribute('data-keterangan');
        document.getElementById('det_tempo').innerText = btn.getAttribute('data-tempo');
        document.getElementById('det_rekening').innerText = btn.getAttribute('data-rekening');
        
        let filePenawaran = btn.getAttribute('data-file');
        if (filePenawaran) {
            let fileArray = filePenawaran.split(',');
            let fileHtml = '';
            fileArray.forEach((file, index) => {
                if (file.trim() !== '') {
                    fileHtml += `<a href="../uploads/penawaran/${file.trim()}" target="_blank" class="btn btn-sm btn-outline-primary me-1 mb-1"><i class="fas fa-file-pdf me-1"></i> Berkas ${index+1}</a> `;
                }
            });
            document.getElementById('det_file').innerHTML = fileHtml !== '' ? fileHtml : '<span class="text-muted fst-italic">Tidak tersedia</span>';
        } else {
            document.getElementById('det_file').innerHTML = '<span class="text-muted fst-italic">Tidak tersedia</span>';
        }
    }

    function siapkanRevisi(id, vendor) {
        document.getElementById('idPengajuanRevisi').value = id;
        document.getElementById('namaVendorRevisi').innerText = vendor;
    }

    function siapkanTolak(id, vendor) {
        document.getElementById('idPengajuanTolak').value = id;
        document.getElementById('namaVendorTolak').innerText = vendor;
    }

    function konfirmasiSetuju(id) {
        Swal.fire({
            title: 'Setujui Pengajuan?',
            text: "Pengajuan ini akan diteruskan ke Direktur Utama.",
            icon: 'question', showCancelButton: true, confirmButtonColor: '#008a3d', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Setujui!'
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('form-setuju-' + id).submit(); }
        })
    }
</script>

<?php 
if(isset($_SESSION['swal_msg'])) { ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({ title: 'Informasi', text: '<?php echo $_SESSION['swal_msg']; ?>', icon: '<?php echo $_SESSION['swal_icon']; ?>', confirmButtonColor: '#008a3d' });
        });
    </script>
<?php unset($_SESSION['swal_msg']); unset($_SESSION['swal_icon']); } ?>

<?php require 'footer.php'; ?>