<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['tambah_divisi'])) {
    $nama = trim($_POST['nama_divisi']);
    $stmt = mysqli_prepare($koneksi, "INSERT INTO divisi (nama_divisi) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $nama);
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg'] = "Divisi berhasil ditambahkan!"; $_SESSION['swal_icon'] = "success";
    }
    header("Location: kelola_divisi.php"); exit();
}

if (isset($_POST['edit_divisi'])) {
    $id = $_POST['id_divisi'];
    $nama = trim($_POST['nama_divisi']);
    $stmt = mysqli_prepare($koneksi, "UPDATE divisi SET nama_divisi = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $nama, $id);
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg'] = "Divisi berhasil diperbarui!"; $_SESSION['swal_icon'] = "success";
    }
    header("Location: kelola_divisi.php"); exit();
}

if (isset($_POST['hapus_divisi'])) {
    $id = $_POST['id_divisi'];
    // Opsional: Cek apakah divisi masih dipakai user
    $q_cek = mysqli_query($koneksi, "SELECT id FROM users WHERE divisi_id = '$id'");
    if(mysqli_num_rows($q_cek) > 0) {
        $_SESSION['swal_msg'] = "Gagal! Divisi ini masih digunakan oleh pengguna aktif."; $_SESSION['swal_icon'] = "error";
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM divisi WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_msg'] = "Divisi berhasil dihapus!"; $_SESSION['swal_icon'] = "success";
        }
    }
    header("Location: kelola_divisi.php"); exit();
}

// Ambil Data
$q_divisi = mysqli_query($koneksi, "SELECT * FROM divisi ORDER BY nama_divisi ASC");

require 'header.php';
?>

<style>
    .card-clean { background: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); animation: slideUp 0.4s ease forwards; }
    .card-header-clean { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    
    .table-modern thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; }
    .table-modern tbody td { padding: 15px 20px; font-size: 0.9rem; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    
    .btn-tambah { background: var(--lkpi-green); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; transition: 0.2s; }
    .btn-tambah:hover { background: #007031; transform: translateY(-1px); }
</style>

<div class="card-clean">
    <div class="card-header-clean">
        <h5 class="m-0 fw-bold"><i class="fas fa-network-wired text-primary me-2"></i> Master Data Divisi</h5>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="fas fa-plus me-1"></i> Tambah Divisi</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th width="10%" class="text-center">No</th>
                    <th>Nama Divisi / Departemen</th>
                    <th width="20%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while($row = mysqli_fetch_assoc($q_divisi)) { ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['nama_divisi']); ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary border-0 me-1" onclick="editDivisi(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_divisi'])); ?>')" data-bs-toggle="modal" data-bs-target="#modalEdit" title="Edit"><i class="fas fa-edit"></i></button>
                        
                        <form action="" method="POST" class="d-inline" id="formHapus-<?php echo $row['id']; ?>">
                            <input type="hidden" name="id_divisi" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="hapus_divisi" value="1">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="konfirmasiHapus(<?php echo $row['id']; ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="" method="POST">
                <div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="fw-bold">Tambah Divisi Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body px-4">
                    <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Nama Divisi</label>
                    <input type="text" name="nama_divisi" class="form-control" required placeholder="Contoh: Keuangan, Pajak, IT...">
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_divisi" class="btn btn-primary px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="" method="POST">
                <div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="fw-bold">Edit Divisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body px-4">
                    <input type="hidden" name="id_divisi" id="edit_id">
                    <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Nama Divisi</label>
                    <input type="text" name="nama_divisi" id="edit_nama" class="form-control" required>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_divisi" class="btn btn-primary px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editDivisi(id, nama) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
    }
    function konfirmasiHapus(id) {
        Swal.fire({ title: 'Hapus Divisi?', text: "Tindakan ini tidak bisa dibatalkan.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => { if (result.isConfirmed) document.getElementById('formHapus-' + id).submit(); })
    }
</script>

<?php 
if(isset($_SESSION['swal_msg'])) { ?>
    <script> document.addEventListener('DOMContentLoaded', function() { Swal.fire({ title: 'Informasi', text: '<?php echo $_SESSION['swal_msg']; ?>', icon: '<?php echo $_SESSION['swal_icon']; ?>', confirmButtonColor: '#008a3d' }); }); </script>
<?php unset($_SESSION['swal_msg']); unset($_SESSION['swal_icon']); } ?>

<?php require 'footer.php'; ?>