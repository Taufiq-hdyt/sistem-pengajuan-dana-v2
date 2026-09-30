<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah Finance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../index.php");
    exit();
}

// Proses jika tombol Upload ditekan
if (isset($_POST['upload_transfer'])) {
    $id_pengajuan = $_POST['id_pengajuan'];
    
    // Cek apakah ada file yang diunggah
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
        $nama_file = $_FILES['bukti_transfer']['name'];
        $tmp_file  = $_FILES['bukti_transfer']['tmp_name'];
        $ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png', 'pdf'];
        
        $x = explode('.', $nama_file);
        $ekstensi = strtolower(end($x));

        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            // Buat nama unik
            $file_transfer = uniqid() . '-TF-' . $nama_file;
            $path_upload = '../uploads/transfer/' . $file_transfer;
            
            if (move_uploaded_file($tmp_file, $path_upload)) {
                // Update database — sekaligus catat tanggal & waktu transfer saat ini
                $query_update = "UPDATE pengajuan SET bukti_transfer = ?, tanggal_transfer = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($koneksi, $query_update);
                mysqli_stmt_bind_param($stmt, "si", $file_transfer, $id_pengajuan);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['swal_msg'] = "Bukti transfer berhasil diunggah! SPV sekarang dapat melihatnya.";
                    $_SESSION['swal_icon'] = "success";
                } else {
                    $_SESSION['swal_msg'] = "Gagal menyimpan ke database.";
                    $_SESSION['swal_icon'] = "error";
                }
            } else {
                $_SESSION['swal_msg'] = "Gagal memindahkan file ke folder tujuan.";
                $_SESSION['swal_icon'] = "error";
            }
        } else {
            $_SESSION['swal_msg'] = "Gagal! Format file hanya boleh JPG, JPEG, PNG, atau PDF.";
            $_SESSION['swal_icon'] = "error";
        }
    } else {
        $_SESSION['swal_msg'] = "File bukti transfer tidak ditemukan atau terjadi kesalahan saat upload.";
        $_SESSION['swal_icon'] = "error";
    }
    
    // Terapkan pola Post/Redirect/Get untuk menghindari resubmission
    header("Location: proses_transfer.php");
    exit();
}

// Ambil data: Syaratnya sudah Disetujui sepenuhnya, TAPI bukti_transfer masih kosong
$query = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi 
          FROM pengajuan p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN divisi d ON u.divisi_id = d.id 
          WHERE p.status_keseluruhan = 'Disetujui' 
          AND p.bukti_transfer IS NULL 
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
    
    /* Soft Badges untuk Kategori/Divisi */
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

    .rekening-box { 
        font-family: 'Consolas', 'Courier New', monospace; 
        background: #f8fafc; 
        padding: 8px 12px; 
        border-radius: 8px; 
        font-size: 0.85rem; 
        color: #334155; 
        display: inline-block; 
        margin-top: 5px; 
        border: 1px dashed #cbd5e1;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Tombol Aksi */
    .btn-upload-tf {
        background-color: var(--lkpi-green);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: 0.2s;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .btn-upload-tf:hover { background: #007031; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0, 138, 61, 0.2); }

    .form-upload-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .custom-file-input {
        font-size: 0.75rem;
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        color: #64748b;
        width: 100%;
        cursor: pointer;
    }
    .custom-file-input:focus {
        border-color: var(--lkpi-green);
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 138, 61, 0.15);
    }

    /* Warning Text untuk Jatuh Tempo Dekat */
    .text-urgent {
        color: #dc2626;
        font-weight: 700;
        background: #fef2f2;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        display: inline-block;
        margin-top: 4px;
    }
</style>

<div class="card-clean">
    <div class="card-header-clean">
        <h5 class="card-title"><i class="fas fa-money-bill-transfer text-primary me-2"></i> Antrean Pencairan Dana</h5>
    </div>
    
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 25%;">Penerima & Nominal</th>
                    <th style="width: 25%;">Jatuh Tempo & Divisi</th>
                    <th style="width: 25%;">Rekening Tujuan</th>
                    <th class="text-center pe-4" style="width: 25%;">Aksi (Upload Bukti)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                        $nama_divisi = !empty($row['nama_divisi']) ? $row['nama_divisi'] : 'Tanpa Divisi';
                        
                        // Menghitung selisih hari jatuh tempo
                        $tgl_tempo_obj = new DateTime($row['tanggal_jatuh_tempo']);
                        $hari_ini = new DateTime(date('Y-m-d'));
                        $selisih = $hari_ini->diff($tgl_tempo_obj);
                        $is_urgent = ($selisih->invert == 1 || $selisih->days <= 2); // Terlewat atau H-2
                        
                        $tgl_tempo_format = date('d M Y', strtotime($row['tanggal_jatuh_tempo']));
                ?>
                <tr>
                    <td class="ps-4">
                        <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                        <div class="nominal-text mt-1"><?php echo $nominal; ?></div>
                    </td>
                    <td>
                        <strong class="d-block text-dark mb-1"><i class="far fa-calendar-alt me-1 text-muted"></i> <?php echo $tgl_tempo_format; ?></strong>
                        <?php if ($is_urgent) { echo '<span class="text-urgent"><i class="fas fa-exclamation-circle me-1"></i>Segera Diproses</span><br>'; } ?>
                        <span class="badge-soft mt-2"><i class="fas fa-user-tag me-1"></i><?php echo htmlspecialchars($row['nama_spv']); ?> (<?php echo htmlspecialchars($nama_divisi); ?>)</span>
                    </td>
                    <td>
                        <div class="rekening-box">
                            <i class="fas fa-building-columns text-muted me-1"></i> <?php echo htmlspecialchars($row['rekening_tujuan']); ?>
                        </div>
                    </td>
                    <td class="pe-4 align-middle">
                        <form action="" method="POST" enctype="multipart/form-data" class="form-upload-group">
                            <input type="hidden" name="id_pengajuan" value="<?php echo $row['id']; ?>">
                            <input type="file" name="bukti_transfer" accept=".jpg, .jpeg, .png, .pdf" required class="custom-file-input">
                            <button type="submit" name="upload_transfer" class="btn-upload-tf">
                                <i class="fas fa-cloud-arrow-up"></i> Upload Bukti
                            </button>
                        </form>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                    echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='fas fa-check-circle fa-3x mb-3 text-success opacity-25 d-block'></i> Tidak ada antrean transfer saat ini. Semua pengajuan telah dicairkan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Menampilkan notifikasi SweetAlert jika ada pesan di session
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

<?php require 'footer.php'; ?>