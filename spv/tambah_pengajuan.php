<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah SPV
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'spv') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];

// Proses saat tombol submit ditekan
if (isset($_POST['submit_pengajuan'])) {
    $nama_vendor         = trim($_POST['nama_vendor']);
    $jenis_pengajuan     = $_POST['jenis_pengajuan'];
    $biaya_diajukan      = $_POST['biaya_diajukan'];
    $keterangan          = trim($_POST['keterangan']);
    $tanggal_jatuh_tempo = $_POST['tanggal_jatuh_tempo'];
    $rekening_tujuan     = trim($_POST['rekening_tujuan']);
    
    // Status awal diatur ke Pending
    $status_manager     = 'Pending';
    $status_finance     = 'Pending';
    $status_dirut       = 'Pending';
    $status_dirkeu      = 'Pending';
    $status_keseluruhan = 'Pending';

    // Proses Upload File Penawaran (Bisa Multiple)
    $file_penawaran_array = [];
    $upload_berhasil = true;

    if (isset($_FILES['file_penawaran']['name'][0]) && $_FILES['file_penawaran']['error'][0] !== 4) {
        $ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png', 'pdf'];
        
        foreach ($_FILES['file_penawaran']['name'] as $key => $nama_file) {
            $tmp_file  = $_FILES['file_penawaran']['tmp_name'][$key];
            $error = $_FILES['file_penawaran']['error'][$key];

            if ($error === 0) {
                $x = explode('.', $nama_file);
                $ekstensi = strtolower(end($x));

                if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
                    // Buat nama file unik
                    $nama_file_baru = uniqid() . '-' . $key . '-PENAWARAN.' . $ekstensi;
                    $path_upload = '../uploads/penawaran/' . $nama_file_baru;
                    
                    if (move_uploaded_file($tmp_file, $path_upload)) {
                        $file_penawaran_array[] = $nama_file_baru;
                    } else {
                        $upload_berhasil = false;
                    }
                } else {
                    $upload_berhasil = false;
                    $_SESSION['swal_msg'] = "Gagal! Format file penawaran hanya boleh JPG, JPEG, PNG, atau PDF.";
                    $_SESSION['swal_icon'] = "error";
                    header("Location: tambah_pengajuan.php");
                    exit();
                }
            }
        }
        
        // Gabungkan semua nama file dengan koma
        if (!empty($file_penawaran_array)) {
            $file_penawaran = implode(',', $file_penawaran_array);
        } else {
            $file_penawaran = NULL;
        }
    } else {
        $file_penawaran = NULL;
    }

    // Query Insert ke Database (Menambahkan status_manager)
    $query = "INSERT INTO pengajuan (user_id, nama_vendor, jenis_pengajuan, biaya_diajukan, keterangan, tanggal_jatuh_tempo, rekening_tujuan, file_penawaran, status_manager, status_finance, status_dirut, status_dirkeu, status_keseluruhan, tanggal_dibuat) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($koneksi, $query);
    // Bind param juga disesuaikan (tambah satu 's' menjadi 13 parameter sebelum NOW())
    mysqli_stmt_bind_param($stmt, "issdsssssssss", $user_id, $nama_vendor, $jenis_pengajuan, $biaya_diajukan, $keterangan, $tanggal_jatuh_tempo, $rekening_tujuan, $file_penawaran, $status_manager, $status_finance, $status_dirut, $status_dirkeu, $status_keseluruhan);
    
    if (mysqli_stmt_execute($stmt)) {
        // Menggunakan flag untuk men-trigger SweetAlert di halaman ini sebelum redirect
        $pengajuan_berhasil = true;
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data.', 'error');
                });
              </script>";
    }
}

// Memanggil Header
require 'header.php';
?>

<style>
    /* Styling khusus form agar senada dengan tema */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        animation: slideUp 0.4s ease forwards;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 10px 15px;
        font-size: 0.9rem;
        color: #334155;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--lkpi-green);
        box-shadow: 0 0 0 3px rgba(0, 138, 61, 0.15);
        outline: none;
    }

    .form-control::placeholder {
        color: #94a3b8;
    }

    .input-group-text {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-right: none;
        color: #64748b;
        font-weight: 600;
        border-radius: 8px 0 0 8px;
    }

    .form-control.with-addon {
        border-left: none;
        border-radius: 0 8px 8px 0;
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
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-submit:hover {
        background-color: #007031;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 138, 61, 0.2);
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="form-card">
            <div class="d-flex align-items-center mb-4 pb-3" style="border-bottom: 2px dashed #f1f5f9;">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.25rem;">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--text-main);">Formulir Pengajuan Dana</h4>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">Lengkapi data di bawah ini untuk mengajukan permohonan pencairan dana ke Departemen Finance.</p>
                </div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="row g-4 mb-4">
                    <!-- Kiri: Vendor -->
                    <div class="col-md-7">
                        <label class="form-label">Nama Vendor / Penerima</label>
                        <input type="text" name="nama_vendor" class="form-control" required placeholder="Contoh: PT. Sumber Makmur / Toko Berkah">
                    </div>
                    
                    <!-- Kanan: Tanggal Tempo -->
                    <div class="col-md-5">
                        <label class="form-label text-danger">Tanggal Jatuh Tempo <i class="fas fa-info-circle ms-1" title="Batas maksimal pembayaran" style="cursor:help;"></i></label>
                        <input type="date" name="tanggal_jatuh_tempo" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Kiri: Nominal -->
                    <div class="col-md-7">
                        <label class="form-label">Total Biaya Diajukan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="biaya_diajukan" class="form-control with-addon" placeholder="5000000" min="1000" required>
                        </div>
                    </div>
                    
                    <!-- Kanan: Jenis Pengajuan -->
                    <div class="col-md-5">
                        <label class="form-label">Jenis Pengajuan</label>
                        <select name="jenis_pengajuan" class="form-select" required>
                            <option value="" selected disabled>-- Pilih Kategori --</option>
                            <option value="Non-PPN">Transaksi Non-PPN</option>
                            <option value="PPN">Transaksi PPN (Pajak)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Rekening Tujuan Pencairan</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-money-check-alt"></i></span>
                        <input type="text" name="rekening_tujuan" class="form-control with-addon" placeholder="Misal: BCA 123456789 a.n PT Sumber Makmur" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Keterangan / Keperluan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Jelaskan secara rinci untuk keperluan apa dana ini diajukan..." required></textarea>
                </div>

                <div class="mb-5 p-4 rounded-3" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label text-primary mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Penawaran Harga (Wajib)</label>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="tambahFileRow()"><i class="fas fa-plus"></i> Tambah File</button>
                    </div>
                    
                    <div id="fileUploadContainer">
                        <div class="input-group mb-2 file-row">
                            <input type="file" name="file_penawaran[]" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required>
                            <button type="button" class="btn btn-outline-danger" onclick="hapusFileRow(this)" disabled><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="form-text mt-2 text-muted" style="font-size: 0.75rem;">
                        Format yang didukung: <strong>JPG, JPEG, PNG, atau PDF</strong>. Anda dapat memilih dokumen satu per satu dengan menekan tombol <b>Tambah File</b>.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 border-top pt-4">
                    <button type="reset" class="btn btn-light border px-4 fw-semibold text-muted">Reset</button>
                    <button type="submit" name="submit_pengajuan" class="btn-submit">
                        Kirim Pengajuan <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Menampilkan notifikasi SweetAlert jika ada pesan di session (Error saat upload)
if(isset($_SESSION['swal_msg'])) { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

<!-- Tambahkan script JS untuk kontrol tambah/hapus input file -->
<script>
    function tambahFileRow() {
        const container = document.getElementById('fileUploadContainer');
        const row = document.createElement('div');
        row.className = 'input-group mb-2 file-row';
        row.innerHTML = `
            <input type="file" name="file_penawaran[]" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required>
            <button type="button" class="btn btn-outline-danger" onclick="hapusFileRow(this)"><i class="fas fa-trash"></i></button>
        `;
        // Animasi muncul perlahan
        row.style.opacity = "0";
        container.appendChild(row);
        setTimeout(() => row.style.opacity = "1", 50);
        row.style.transition = "opacity 0.3s ease";
        
        updateDeleteButtons();
    }

    function hapusFileRow(btn) {
        const row = btn.closest('.file-row');
        row.style.opacity = "0";
        setTimeout(() => {
            row.remove();
            updateDeleteButtons();
        }, 300);
    }

    function updateDeleteButtons() {
        const rows = document.querySelectorAll('.file-row');
        const deleteBtns = document.querySelectorAll('.file-row .btn-outline-danger');
        // Jika hanya sisa 1 kolom, disable tombol hapusnya agar wajib ada minimal 1 file
        if (rows.length === 1) {
            deleteBtns[0].disabled = true;
        } else {
            deleteBtns.forEach(btn => btn.disabled = false);
        }
    }
</script>

<?php 
// Menampilkan SweetAlert SUKSES dan menunda redirect sampai tombol "OK" diklik
if(isset($pengajuan_berhasil) && $pengajuan_berhasil) { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengajuan dana dan bukti penawaran berhasil dikirim!',
                icon: 'success',
                confirmButtonColor: '#008a3d',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect dilakukan melalui JavaScript setelah user klik OK
                    window.location.href = 'riwayat_pengajuan.php';
                }
            });
        });
    </script>
<?php } ?>

<?php require 'footer.php'; ?>