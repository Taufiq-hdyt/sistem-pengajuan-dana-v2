<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah SPV
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'spv') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pastikan ada parameter ID
if (!isset($_GET['id'])) {
    $_SESSION['swal_msg'] = "ID Pengajuan tidak ditemukan!";
    $_SESSION['swal_icon'] = "error";
    header("Location: laporan.php");
    exit();
}
$id_pengajuan = (int) $_GET['id'];

$upload_dir_nota = '../uploads/nota/';
$ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png', 'pdf'];
$max_size = 5000000; // 5MB

// =========================================================================
// 1A. LOGIKA BACKEND: HAPUS SATU FILE NOTA YANG SUDAH TERSIMPAN
// =========================================================================
if (isset($_POST['hapus_nota'])) {
    $file_hapus = $_POST['nama_file'];

    $stmt = $koneksi->prepare("SELECT bukti_nota FROM pengajuan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id_pengajuan, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row_cek = $res->fetch_assoc();

    if ($row_cek) {
        $daftar_file = !empty($row_cek['bukti_nota']) ? explode(',', $row_cek['bukti_nota']) : [];
        $daftar_file = array_map('trim', $daftar_file);
        $daftar_baru = array_filter($daftar_file, function ($f) use ($file_hapus) {
            return $f !== $file_hapus;
        });
        $gabungan_baru = implode(',', $daftar_baru);

        $stmt2 = $koneksi->prepare("UPDATE pengajuan SET bukti_nota = ? WHERE id = ? AND user_id = ?");
        $stmt2->bind_param("sii", $gabungan_baru, $id_pengajuan, $user_id);
        if ($stmt2->execute()) {
            $path_file = $upload_dir_nota . $file_hapus;
            if (file_exists($path_file)) {
                @unlink($path_file);
            }
            $_SESSION['swal_msg']  = "Nota berhasil dihapus.";
            $_SESSION['swal_icon'] = "success";
        } else {
            $_SESSION['swal_msg']  = "Gagal menghapus nota: " . $koneksi->error;
            $_SESSION['swal_icon'] = "error";
        }
    } else {
        $_SESSION['swal_msg']  = "Data pengajuan tidak ditemukan.";
        $_SESSION['swal_icon'] = "error";
    }

    header("Location: laporan_pengajuan.php?id=" . $id_pengajuan);
    exit();
}

// Ambil info pengajuan untuk ditampilkan di header
$query_info = "SELECT * FROM pengajuan WHERE id = ? AND user_id = ?";
$stmt_info = mysqli_prepare($koneksi, $query_info);
mysqli_stmt_bind_param($stmt_info, "ii", $id_pengajuan, $user_id);
mysqli_stmt_execute($stmt_info);
$result_info = mysqli_stmt_get_result($stmt_info);

if (mysqli_num_rows($result_info) == 0) {
    $_SESSION['swal_msg'] = "Data tidak valid atau bukan milik Anda!";
    $_SESSION['swal_icon'] = "error";
    header("Location: laporan.php");
    exit();
}
$data = mysqli_fetch_assoc($result_info);

$status_laporan_sekarang = strtolower(trim($data['status_laporan'] ?? ''));
$catatan_laporan = htmlspecialchars($data['catatan_revisi'] ?? '');
$daftar_nota_tersimpan = !empty($data['bukti_nota']) ? array_filter(array_map('trim', explode(',', $data['bukti_nota']))) : [];

// =========================================================================
// 1B. LOGIKA BACKEND: PROSES SIMPAN / KIRIM LAPORAN (Bisa Multiple Upload)
// =========================================================================
if (isset($_POST['submit_laporan'])) {
    $rincian = trim($_POST['rincian_terformat']);
    $file_nota_baru_array = [];
    $upload_berhasil = true;

    // Cek apakah ada file baru yang diunggah (dari salah satu atau beberapa baris input)
    if (isset($_FILES['bukti_nota_baru'])) {
        foreach ($_FILES['bukti_nota_baru']['name'] as $key => $nama_file_grup) {
            // Setiap baris input bisa berisi banyak file (attribute multiple),
            // jadi $nama_file_grup bisa berupa array atau string tunggal.
            if (is_array($nama_file_grup)) {
                foreach ($nama_file_grup as $sub_key => $nama_file) {
                    $tmp_file = $_FILES['bukti_nota_baru']['tmp_name'][$key][$sub_key];
                    $error    = $_FILES['bukti_nota_baru']['error'][$key][$sub_key];
                    $size     = $_FILES['bukti_nota_baru']['size'][$key][$sub_key];
                    if ($error === 0 && $nama_file !== '') {
                        $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
                        if (in_array($ekstensi, $ekstensi_diperbolehkan) && $size <= $max_size) {
                            $file_nota = uniqid() . '-' . $key . $sub_key . '-NOTA.' . $ekstensi;
                            if (move_uploaded_file($tmp_file, $upload_dir_nota . $file_nota)) {
                                $file_nota_baru_array[] = $file_nota;
                            } else {
                                $upload_berhasil = false;
                            }
                        } else {
                            $upload_berhasil = false;
                        }
                    }
                }
            } else {
                $tmp_file = $_FILES['bukti_nota_baru']['tmp_name'][$key];
                $error    = $_FILES['bukti_nota_baru']['error'][$key];
                $size     = $_FILES['bukti_nota_baru']['size'][$key];
                if ($error === 0 && $nama_file_grup !== '') {
                    $ekstensi = strtolower(pathinfo($nama_file_grup, PATHINFO_EXTENSION));
                    if (in_array($ekstensi, $ekstensi_diperbolehkan) && $size <= $max_size) {
                        $file_nota = uniqid() . '-' . $key . '-NOTA.' . $ekstensi;
                        if (move_uploaded_file($tmp_file, $upload_dir_nota . $file_nota)) {
                            $file_nota_baru_array[] = $file_nota;
                        } else {
                            $upload_berhasil = false;
                        }
                    } else {
                        $upload_berhasil = false;
                    }
                }
            }
        }
    }

    if (!$upload_berhasil) {
        $_SESSION['swal_msg']  = "Salah satu file gagal diunggah! Format hanya boleh JPG, JPEG, PNG, atau PDF, maksimal 5MB.";
        $_SESSION['swal_icon'] = "error";
        header("Location: laporan_pengajuan.php?id=" . $id_pengajuan);
        exit();
    }

    // Gabungkan nota LAMA (yang belum dihapus) dengan nota BARU — tidak menimpa, hanya menambah
    $gabungan_semua_nota = implode(',', array_merge($daftar_nota_tersimpan, $file_nota_baru_array));

    // Minimal harus ada 1 nota total (lama + baru) sebelum bisa dikirim
    if (empty($gabungan_semua_nota)) {
        $_SESSION['swal_msg']  = "Minimal 1 bukti nota wajib diunggah.";
        $_SESSION['swal_icon'] = "warning";
        header("Location: laporan_pengajuan.php?id=" . $id_pengajuan);
        exit();
    }

    $query_update = "UPDATE pengajuan SET rincian_pembelian = ?, bukti_nota = ?, status_laporan = 'Menunggu Validasi' WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($koneksi, $query_update);
    mysqli_stmt_bind_param($stmt, "ssii", $rincian, $gabungan_semua_nota, $id_pengajuan, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg']  = "Laporan berhasil dikirim! Menunggu validasi Finance.";
        $_SESSION['swal_icon'] = "success";
    } else {
        $_SESSION['swal_msg']  = "Gagal menyimpan ke database: " . $koneksi->error;
        $_SESSION['swal_icon'] = "error";
    }
    header("Location: laporan.php");
    exit();
}

// Memanggil Header
require 'header.php';
?>

<style>
    /* Styling khusus form agar senada dengan tema SaaS */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        animation: slideUp 0.4s ease forwards;
    }

    /* Info Box Elegan */
    .info-box {
        background-color: #f8fafc;
        padding: 20px 25px;
        border-radius: 12px;
        border-left: 4px solid var(--lkpi-green);
        margin-bottom: 30px;
    }

    /* Styling Tabel Dinamis */
    .table-modern th {
        background-color: transparent;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 12px;
    }
    
    .table-modern td {
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 8px;
    }

    .form-control-table {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        width: 100%;
        font-size: 0.875rem;
        color: #334155;
        transition: all 0.2s ease;
    }

    .form-control-table:focus {
        border-color: var(--lkpi-green);
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 138, 61, 0.15);
    }

    .form-control-table[readonly] {
        background-color: #f0fdf4;
        font-weight: 700;
        color: var(--lkpi-green);
        border-color: transparent;
        text-align: right;
    }

    /* Tombol Aksi Tabel */
    .btn-tambah {
        background: #dcfce7;
        color: #16a34a;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }
    .btn-tambah:hover { background: #bbf7d0; color: #15803d; }
    
    .btn-hapus {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .btn-hapus:hover { background: #fecaca; color: #b91c1c; }

    /* Upload Zone */
    .upload-zone {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 12px;
        padding: 25px;
        transition: all 0.2s ease;
    }
    .upload-zone input[type="file"] {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-submit {
        background-color: var(--lkpi-green);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 10px;
    }
    .btn-submit:hover {
        background-color: #007031;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 138, 61, 0.2);
    }

    .nota-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 6px;
        font-size: 0.85rem;
    }
    .nota-item a { color: #0284c7; text-decoration: none; font-weight: 600; }
    .nota-item a:hover { text-decoration: underline; }
    .nota-hapus-btn {
        background: none;
        border: none;
        color: #dc2626;
        font-size: 0.9rem;
        cursor: pointer;
        padding: 2px 8px;
    }
    .nota-hapus-btn:hover { color: #991b1b; }

    .file-row-nota { margin-bottom: 8px; }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-11">
        <div class="form-card">
            
            <div class="d-flex align-items-center mb-4 pb-3" style="border-bottom: 2px dashed #f1f5f9;">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.25rem;">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--text-main);">Laporan Pertanggungjawaban</h4>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">Rincikan pembelanjaan dan kelola bukti nota (bisa ditambah/dihapus satu per satu).</p>
                </div>
            </div>

            <!-- Info Box Modern -->
            <div class="info-box d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="d-block text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Vendor Tujuan Pembelian</span>
                    <strong class="text-dark fs-5"><i class="fas fa-store me-2 text-muted"></i><?php echo htmlspecialchars($data['nama_vendor']); ?></strong>
                </div>
                <div class="text-md-end">
                    <span class="d-block text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Dana Tersedia (Maksimal)</span>
                    <strong class="fs-4" style="color: var(--lkpi-green);">Rp <?php echo number_format($data['biaya_diajukan'], 0, ',', '.'); ?></strong>
                </div>
            </div>

            <!-- Catatan Revisi dari Finance (muncul otomatis jika ada) -->
            <?php if ($status_laporan_sekarang == 'revisi'): ?>
            <div class="alert alert-warning d-flex align-items-start mb-4" style="border-radius: 12px; border: 1px dashed #fcd34d;">
                <i class="fas fa-exclamation-circle fs-5 me-3 mt-1 text-warning"></i>
                <div>
                    <strong class="d-block mb-1">Catatan Revisi dari Finance:</strong>
                    <span style="font-size: 0.875rem;"><?php echo !empty($catatan_laporan) ? $catatan_laporan : 'Tidak ada catatan spesifik. Silakan perbaiki rincian/nota Anda.'; ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['rincian_pembelian'])): ?>
            <div class="mb-4">
                <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem;"><i class="fas fa-clock-rotate-left me-2 text-muted"></i>Rincian Tersimpan Saat Ini</h6>
                <textarea class="form-control" rows="4" readonly style="font-size: 0.8rem; background: #f8fafc; font-family: monospace;"><?php echo htmlspecialchars($data['rincian_pembelian']); ?></textarea>
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">* Isi tabel di bawah untuk memperbarui rincian ini saat mengirim ulang.</small>
            </div>
            <?php endif; ?>

            <!-- Daftar Nota yang Sudah Diupload (bisa dihapus satu per satu) -->
            <div class="mb-4">
                <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem;"><i class="fas fa-folder-open me-2 text-muted"></i>Nota Tersimpan</h6>
                <div id="daftar_nota_lama">
                    <?php if (!empty($daftar_nota_tersimpan)): ?>
                        <?php foreach ($daftar_nota_tersimpan as $index => $file): ?>
                        <div class="nota-item">
                            <a href="../uploads/nota/<?php echo urlencode($file); ?>" target="_blank">
                                <i class="fas fa-file-lines me-1"></i> Nota <?php echo $index + 1; ?>
                            </a>
                            <button type="button" class="nota-hapus-btn" title="Hapus nota ini" onclick="hapusNotaTersimpan('<?php echo htmlspecialchars($file); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted fst-italic" style="font-size: 0.85rem;">Belum ada nota diupload.</span>
                    <?php endif; ?>
                </div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" onsubmit="siapkanDataLaporan()">
                <!-- Textarea tersembunyi untuk menyimpan string rincian -->
                <textarea name="rincian_terformat" id="rincian_terformat" style="display: none;"></textarea>

                <div class="d-flex justify-content-between align-items-end mb-3 mt-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-list-check me-2 text-muted"></i>Rincian Barang / Jasa</h6>
                    <button type="button" class="btn-tambah" onclick="tambahBaris()"><i class="fas fa-plus me-1"></i> Tambah Baris</button>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-borderless table-modern" id="tabelRincian" style="min-width: 650px;">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Nama Barang / Jasa</th>
                                <th style="width: 25%;">Harga Satuan (Rp)</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 20%; text-align:right;">Sub-Total (Rp)</th>
                                <th style="width: 10%; text-align: center;">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" class="form-control-table input-nama" required placeholder="Ketik nama barang..."></td>
                                <td><input type="number" class="form-control-table input-harga" required placeholder="0" min="0" oninput="hitungTotalKeseluruhan()"></td>
                                <td><input type="number" class="form-control-table input-qty" required value="1" min="1" oninput="hitungTotalKeseluruhan()"></td>
                                <td><input type="text" class="form-control-table input-total" readonly value="Rp 0"></td>
                                <td style="text-align: center;">-</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold pt-4 text-muted" style="white-space: nowrap;">TOTAL KESELURUHAN:</td>
                                <td class="pt-4"><input type="text" id="total_semua" class="form-control-table" readonly value="Rp 0" style="font-size: 1.1rem; min-width: 150px;"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Upload Nota Baru: bisa pilih banyak sekaligus per baris, atau tambah baris untuk memilih bertahap -->
                <div class="upload-zone mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-cloud-upload-alt me-2 text-muted"></i>Tambah Bukti Nota / Kuitansi Baru</h6>
                        <button type="button" class="btn-tambah" onclick="tambahFileRowNota()"><i class="fas fa-plus me-1"></i> Tambah Baris File</button>
                    </div>
                    <p class="text-muted mb-3" style="font-size: 0.85rem;">
                        Format JPG, PNG, atau PDF (maks 5MB/file). Anda bisa memilih beberapa file sekaligus dalam satu baris,
                        atau klik "Tambah Baris File" untuk menambah nota secara bertahap.
                    </p>
                    <div id="fileUploadContainerNota">
                        <div class="input-group mb-2 file-row-nota">
                            <input type="file" name="bukti_nota_baru[]" class="form-control" accept=".jpg, .jpeg, .png, .pdf" multiple>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusFileRowNota(this)" disabled><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">* Boleh dikosongkan jika hanya ingin memperbarui rincian di atas.</small>
                </div>

                <button type="submit" name="submit_laporan" class="btn-submit">
                    Kirim / Perbarui Laporan ke Finance <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </form>

            <!-- Form terpisah khusus hapus nota tersimpan -->
            <form action="" method="POST" id="formHapusNota" class="d-none">
                <input type="hidden" name="nama_file" id="hapus_nama_file">
                <input type="hidden" name="hapus_nota" value="1">
            </form>

        </div>
    </div>
</div>

<script>
    function tambahBaris() {
        let tbody = document.querySelector("#tabelRincian tbody");
        let row = document.createElement("tr");
        row.innerHTML = `
            <td><input type="text" class="form-control-table input-nama" required placeholder="Ketik nama barang..."></td>
            <td><input type="number" class="form-control-table input-harga" required placeholder="0" min="0" oninput="hitungTotalKeseluruhan()"></td>
            <td><input type="number" class="form-control-table input-qty" required value="1" min="1" oninput="hitungTotalKeseluruhan()"></td>
            <td><input type="text" class="form-control-table input-total" readonly value="Rp 0"></td>
            <td style="text-align: center;"><button type="button" class="btn-hapus" onclick="hapusBaris(this)" title="Hapus Baris"><i class="fas fa-trash"></i></button></td>
        `;
        row.style.opacity = "0";
        tbody.appendChild(row);
        setTimeout(() => row.style.opacity = "1", 50);
        row.style.transition = "opacity 0.3s ease";
    }

    function hapusBaris(btn) {
        let row = btn.parentNode.parentNode;
        row.style.opacity = "0";
        setTimeout(() => {
            row.parentNode.removeChild(row);
            hitungTotalKeseluruhan();
        }, 300);
    }

    function hitungTotalKeseluruhan() {
        let baris = document.querySelectorAll("#tabelRincian tbody tr");
        let grandTotal = 0;
        
        baris.forEach(function(row) {
            let hargaRaw = row.querySelector(".input-harga").value;
            let qtyRaw = row.querySelector(".input-qty").value;
            
            let harga = parseInt(hargaRaw) || 0;
            let qty = parseInt(qtyRaw) || 0;
            let subTotal = harga * qty;
            
            row.querySelector(".input-total").value = new Intl.NumberFormat('id-ID', { 
                style: 'currency', 
                currency: 'IDR', 
                minimumFractionDigits: 0 
            }).format(subTotal);
            
            grandTotal += subTotal;
        });
        
        document.getElementById("total_semua").value = new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0 
        }).format(grandTotal);
    }

    document.addEventListener("DOMContentLoaded", function() {
        let inputs = document.querySelectorAll("#tabelRincian tbody tr:first-child input[type='number']");
        inputs.forEach(input => {
            input.setAttribute("oninput", "hitungTotalKeseluruhan()");
        });
        setTimeout(hitungTotalKeseluruhan, 100);
    });

    function siapkanDataLaporan() {
        let baris = document.querySelectorAll("#tabelRincian tbody tr");
        let teksLaporan = "RINCIAN PEMBELIAN:\n";
        let grandTotal = 0;

        baris.forEach(function(row, index) {
            let nama = row.querySelector(".input-nama").value || "Barang Tanpa Nama";
            let hargaRaw = row.querySelector(".input-harga").value;
            let qtyRaw = row.querySelector(".input-qty").value;
            
            let harga = parseInt(hargaRaw) || 0;
            let qty = parseInt(qtyRaw) || 0;
            let total = harga * qty;
            
            grandTotal += total;

            let formatHarga = new Intl.NumberFormat('id-ID').format(harga);
            let formatTotal = new Intl.NumberFormat('id-ID').format(total);
            
            teksLaporan += `${index + 1}. ${qty}x ${nama} (@ Rp ${formatHarga}) = Rp ${formatTotal}\n`;
        });

        let formatGrandTotal = new Intl.NumberFormat('id-ID').format(grandTotal);
        teksLaporan += `\n--------------------------------\nTOTAL KESELURUHAN: Rp ${formatGrandTotal}`;

        document.getElementById("rincian_terformat").value = teksLaporan;
    }

    // ---- Manajemen baris upload nota baru (sebelum submit) ----
    function tambahFileRowNota() {
        const container = document.getElementById('fileUploadContainerNota');
        const row = document.createElement('div');
        row.className = 'input-group mb-2 file-row-nota';
        row.innerHTML = `
            <input type="file" name="bukti_nota_baru[]" class="form-control" accept=".jpg, .jpeg, .png, .pdf" multiple>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusFileRowNota(this)"><i class="fas fa-trash"></i></button>
        `;
        row.style.opacity = "0";
        container.appendChild(row);
        setTimeout(() => row.style.opacity = "1", 50);
        row.style.transition = "opacity 0.3s ease";
        updateDeleteButtonsNota();
    }

    function hapusFileRowNota(btn) {
        const row = btn.closest('.file-row-nota');
        row.style.opacity = "0";
        setTimeout(() => {
            row.remove();
            updateDeleteButtonsNota();
        }, 300);
    }

    function updateDeleteButtonsNota() {
        const rows = document.querySelectorAll('.file-row-nota');
        const deleteBtns = document.querySelectorAll('.file-row-nota .btn-outline-danger');
        if (rows.length === 1) {
            deleteBtns[0].disabled = true;
        } else {
            deleteBtns.forEach(btn => btn.disabled = false);
        }
    }

    // ---- Hapus nota yang SUDAH tersimpan di server (langsung, terpisah dari form utama) ----
    function hapusNotaTersimpan(namaFile) {
        Swal.fire({
            title: 'Hapus nota ini?',
            text: "File yang sudah dihapus tidak bisa dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('hapus_nama_file').value = namaFile;
                document.getElementById('formHapusNota').submit();
            }
        });
    }
</script>

<?php
if(isset($_SESSION['swal_msg'])) { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Informasi',
                text: <?php echo json_encode($_SESSION['swal_msg']); ?>,
                icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
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