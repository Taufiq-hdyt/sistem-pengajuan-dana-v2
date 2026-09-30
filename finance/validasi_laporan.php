<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah Finance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../index.php");
    exit();
}

// Proses jika tombol Validasi atau Kirim Revisi ditekan
if (isset($_POST['proses_laporan'])) {
    $id_pengajuan = $_POST['id_pengajuan'];
    $aksi = $_POST['aksi'];

    if ($aksi === 'valid') {
        $realisasi_dana = isset($_POST['realisasi_dana']) ? (float) $_POST['realisasi_dana'] : 0;

        // Validasi: realisasi dana wajib diisi dan harus lebih dari 0
        if ($realisasi_dana <= 0) {
            $_SESSION['swal_msg']  = "Realisasi dana wajib diisi dan harus lebih dari 0.";
            $_SESSION['swal_icon'] = "warning";
            header("Location: validasi_laporan.php");
            exit();
        }

        // Jika valid, kosongkan catatan revisi sebelumnya, simpan realisasi dana, dan set status selesai
        $query_update = "UPDATE pengajuan SET status_laporan = 'Valid', catatan_revisi = NULL, realisasi_dana = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, "di", $realisasi_dana, $id_pengajuan);

        $_SESSION['swal_msg']  = "Laporan berhasil DIVALIDASI! Realisasi dana: Rp " . number_format($realisasi_dana, 0, ',', '.') . ". Transaksi ini telah selesai sepenuhnya.";
        $_SESSION['swal_icon'] = "success";

    } elseif ($aksi === 'revisi') {
        $catatan = trim($_POST['catatan_revisi']);
        $query_update = "UPDATE pengajuan SET status_laporan = 'Revisi', catatan_revisi = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, "si", $catatan, $id_pengajuan);
        
        $_SESSION['swal_msg'] = "Catatan revisi berhasil dikirim ke SPV.";
        $_SESSION['swal_icon'] = "warning";
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg'] = "Terjadi kesalahan saat memproses laporan.";
        $_SESSION['swal_icon'] = "error";
    }

    // Redirect untuk menghindari resubmission (Post/Redirect/Get)
    header("Location: validasi_laporan.php");
    exit();
}

// Ambil data pengajuan yang laporannya berstatus 'Menunggu Validasi'
$query = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi 
          FROM pengajuan p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN divisi d ON u.divisi_id = d.id 
          WHERE p.status_laporan = 'Menunggu Validasi' 
          ORDER BY p.tanggal_dibuat ASC";
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
        vertical-align: top; /* Rincian bisa panjang, jadi rata atas */
        font-size: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .table-modern tbody tr:hover { background-color: #f8fafc; }

    .vendor-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; display: block; margin-bottom: 3px; }
    .nominal-text { color: var(--lkpi-green); font-weight: 700; font-size: 1rem; }
    
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

    /* Rincian Box Styling */
    .rincian-box { 
        background: #f8fafc; 
        border-left: 3px solid var(--lkpi-green); 
        padding: 12px; 
        font-size: 0.85rem; 
        color: #334155; 
        border-radius: 0 8px 8px 0; 
        font-family: 'Consolas', 'Courier New', monospace;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }

    .link-nota { 
        color: var(--lkpi-green); 
        font-weight: 600; 
        text-decoration: none; 
        display: inline-flex; 
        align-items: center;
        margin-top: 8px; 
        margin-right: 8px; 
        background: #f0fdf4; 
        padding: 6px 10px; 
        border-radius: 6px;
        border: 1px solid #bbf7d0;
        transition: 0.2s;
        font-size: 0.75rem;
    }
    .link-nota:hover { background: #dcfce7; color: #15803d; transform: translateY(-1px); }

    /* Tombol Aksi */
    .btn-acc {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: 0.2s;
        width: 100%;
        display: block;
        text-align: center;
        margin-bottom: 8px;
    }
    .btn-acc:hover { background: #dcfce7; color: #15803d; border-color: #86efac;}

    .btn-rej {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: 0.2s;
        width: 100%;
        display: block;
        text-align: center;
    }
    .btn-rej:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5;}
</style>

<div class="card-clean">
    <div class="card-header-clean">
        <h5 class="card-title"><i class="fas fa-file-circle-check text-primary me-2"></i> Validasi Laporan SPV</h5>
    </div>
    
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 20%;">Diajukan Oleh</th>
                    <th style="width: 20%;">Vendor & Nominal</th>
                    <th style="width: 45%;">Rincian & Bukti Nota</th>
                    <th class="text-center pe-4" style="width: 15%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                        $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';

                        // Coba deteksi "TOTAL KESELURUHAN: Rp xxx.xxx" dari teks rincian yang dibuat SPV,
                        // untuk dijadikan nilai bawaan (default) pada input realisasi dana.
                        $total_rincian_terdeteksi = 0;
                        if (!empty($row['rincian_pembelian']) && preg_match('/TOTAL KESELURUHAN:\s*Rp\s*([\d\.,]+)/i', $row['rincian_pembelian'], $m)) {
                            $angka_bersih = preg_replace('/[^\d]/', '', $m[1]);
                            $total_rincian_terdeteksi = (int) $angka_bersih;
                        }
                ?>
                <tr>
                    <td class="ps-4">
                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['nama_spv']); ?></strong>
                        <span class="badge-soft mt-1"><?php echo htmlspecialchars($nama_divisi); ?></span>
                    </td>
                    <td>
                        <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                        <div class="nominal-text mt-1"><?php echo $nominal; ?></div>
                    </td>
                    <td>
                        <div class="rincian-box mb-2">
                            <strong class="d-block mb-2 text-muted" style="font-family: 'Inter', sans-serif;"><i class="fas fa-receipt me-1"></i> RINCIAN DILAPORKAN:</strong>
                            <?php echo nl2br(htmlspecialchars($row['rincian_pembelian'])); ?>
                        </div>
                        
                        <div class="d-flex flex-wrap">
                        <?php 
                        if (!empty($row['bukti_nota'])) { 
                            $kumpulan_nota = explode(',', $row['bukti_nota']);
                            $no = 1;
                            foreach ($kumpulan_nota as $nota) {
                                $file_bersih = htmlspecialchars(trim($nota));
                                echo '<a href="../uploads/nota/' . $file_bersih . '" target="_blank" class="link-nota"><i class="fas fa-file-invoice me-1"></i> Nota ' . $no . '</a>';
                                $no++;
                            }
                        } else { ?>
                            <span class="text-danger" style="font-size:12px;"><i class="fas fa-exclamation-triangle"></i> Nota tidak ditemukan!</span>
                        <?php } ?>
                        </div>
                    </td>
                    <td class="pe-4">
                        <button type="button" class="btn-acc"
                            data-bs-toggle="modal" data-bs-target="#modalValidasi"
                            onclick="isiModalValidasi(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_vendor'])); ?>', <?php echo (float) $row['biaya_diajukan']; ?>, <?php echo $total_rincian_terdeteksi; ?>)">
                            <i class="fas fa-check me-1"></i> Validasi
                        </button>
                        
                        <button type="button" class="btn-rej" data-bs-toggle="modal" data-bs-target="#modalRevisi" onclick="isiModalRevisi(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_vendor'])); ?>')">
                            <i class="fas fa-rotate-left me-1"></i> Revisi
                        </button>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                    echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='fas fa-folder-open fa-3x mb-3 opacity-25 d-block'></i> Tidak ada laporan yang perlu divalidasi saat ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Validasi & Realisasi Dana -->
<div class="modal fade" id="modalValidasi" tabindex="-1" aria-labelledby="modalValidasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-success" id="modalValidasiLabel">
                    <i class="fas fa-check-circle me-2"></i> Validasi & Realisasi Dana
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <p style="font-size: 0.85rem; color: #64748b;">Laporan untuk vendor: <strong id="namaVendorValidasi" class="text-dark"></strong></p>

                    <input type="hidden" name="id_pengajuan" id="idPengajuanValidasi">
                    <input type="hidden" name="aksi" value="valid">

                    <div class="mb-3 p-3 rounded-3 d-flex justify-content-between align-items-center" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                        <span class="text-muted" style="font-size: 0.8rem;">Dana Diajukan</span>
                        <strong id="danaDiajukanValidasi" class="text-dark" style="font-size: 0.95rem;"></strong>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #334155;">Realisasi Dana (Nominal Sebenarnya Terpakai)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-radius: 8px 0 0 8px; font-size: 0.85rem;">Rp</span>
                            <input type="number" name="realisasi_dana" id="realisasiDanaInput" class="form-control" min="1" step="1" required style="font-size: 0.9rem;">
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                            Otomatis diisi dari total rincian yang dilaporkan SPV — sesuaikan jika berbeda dengan nota aslinya.
                        </small>
                    </div>

                    <div id="selisihRealisasiInfo" class="alert d-none mb-0 py-2" style="border-radius: 10px; font-size: 0.8rem;"></div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-size: 0.85rem; font-weight: 600; border-radius: 8px;">Batal</button>
                    <button type="submit" name="proses_laporan" class="btn btn-success px-4" style="font-size: 0.85rem; font-weight: 600; border-radius: 8px;">
                        <i class="fas fa-check me-1"></i> Validasi & Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Catatan Revisi Bootstrap 5 -->
<div class="modal fade" id="modalRevisi" tabindex="-1" aria-labelledby="modalRevisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-danger" id="modalRevisiLabel">
                    <i class="fas fa-triangle-exclamation me-2"></i> Form Minta Revisi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <p style="font-size: 0.85rem; color: #64748b;">Laporan untuk vendor: <strong id="namaVendorRevisi" class="text-dark"></strong></p>
                    
                    <input type="hidden" name="id_pengajuan" id="idPengajuanRevisi">
                    <input type="hidden" name="aksi" value="revisi">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #334155;">Catatan Perbaikan untuk SPV:</label>
                        <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Contoh: Nota ke-2 buram/tidak terbaca, mohon unggah ulang foto yang jelas..." style="font-size: 0.875rem; border-radius: 8px;"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-size: 0.85rem; font-weight: 600; border-radius: 8px;">Batal</button>
                    <button type="submit" name="proses_laporan" class="btn btn-danger px-4" style="font-size: 0.85rem; font-weight: 600; border-radius: 8px;">Kirim Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ---- Modal Validasi & Realisasi Dana ----
    function isiModalValidasi(id, vendor, biayaDiajukan, totalRincianTerdeteksi) {
        document.getElementById('idPengajuanValidasi').value = id;
        document.getElementById('namaVendorValidasi').innerText = vendor;
        document.getElementById('danaDiajukanValidasi').innerText = 'Rp ' + formatRupiah(biayaDiajukan);

        // Simpan di variabel global (bukan dataset) supaya tidak ada masalah pembacaan ulang
        danaDiajukanSaatIni = biayaDiajukan;

        const input = document.getElementById('realisasiDanaInput');
        input.value = totalRincianTerdeteksi > 0 ? totalRincianTerdeteksi : biayaDiajukan;

        hitungSelisihRealisasi();
    }

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    // Variabel global penyimpan nominal dana yang diajukan untuk pengajuan yang sedang dibuka di modal
    let danaDiajukanSaatIni = 0;

    function hitungSelisihRealisasi() {
        const input = document.getElementById('realisasiDanaInput');
        const realisasi = parseFloat(input.value) || 0;
        const infoBox = document.getElementById('selisihRealisasiInfo');

        if (realisasi <= 0) {
            infoBox.classList.add('d-none');
            return;
        }

        const selisih = realisasi - danaDiajukanSaatIni;
        infoBox.classList.remove('d-none');

        if (selisih > 0) {
            infoBox.className = 'alert alert-warning mb-0 py-2';
            infoBox.innerHTML = '<i class="fas fa-arrow-up me-1"></i> Melebihi dana diajukan sebesar Rp ' + formatRupiah(selisih);
        } else if (selisih < 0) {
            infoBox.className = 'alert alert-success mb-0 py-2';
            infoBox.innerHTML = '<i class="fas fa-arrow-down me-1"></i> Hemat Rp ' + formatRupiah(Math.abs(selisih)) + ' dari dana diajukan';
        } else {
            infoBox.className = 'alert alert-secondary mb-0 py-2';
            infoBox.innerHTML = '<i class="fas fa-equals me-1"></i> Sama persis dengan dana diajukan';
        }
    }

    // Bind ulang event listener secara eksplisit (lebih reliable daripada atribut oninput inline)
    document.addEventListener('DOMContentLoaded', function () {
        const inputRealisasi = document.getElementById('realisasiDanaInput');
        if (inputRealisasi) {
            ['input', 'keyup', 'change'].forEach(function (namaEvent) {
                inputRealisasi.addEventListener(namaEvent, hitungSelisihRealisasi);
            });
        }
    });

    // ---- Modal Catatan Revisi ----
    function isiModalRevisi(id, vendor) {
        document.getElementById('idPengajuanRevisi').value = id;
        document.getElementById('namaVendorRevisi').innerText = vendor;
    }
</script>

<?php 
// Menangkap notifikasi sukses/gagal dari PHP Session
if(isset($_SESSION['swal_msg'])) { ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: 'Informasi',
                text: '<?php echo $_SESSION['swal_msg']; ?>',
                icon: '<?php echo $_SESSION['swal_icon']; ?>',
                confirmButtonColor: '#008a3d'
            });
        });
    </script>
<?php 
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_icon']);
} 
?>

<?php require 'footer.php'; ?>