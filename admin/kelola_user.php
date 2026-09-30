<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];
    
    // Cek Role untuk Divisi
    $q_role = mysqli_query($koneksi, "SELECT nama_role FROM roles WHERE id = '$role_id'");
    $nama_role = mysqli_fetch_assoc($q_role)['nama_role'];
    
    // CATATAN VERSI "1 MANAGER UNTUK SEMUA DIVISI":
    // Hanya SPV yang wajib punya divisi. Manager (dan Finance/Dirut/Dirkeu)
    // sekarang bersifat terpusat/general, sama seperti role lainnya.
    $divisi_id = NULL;
    if (strtolower($nama_role) === 'spv') {
        $divisi_id = !empty($_POST['divisi_id']) ? $_POST['divisi_id'] : NULL;
    }

    // Cek email ganda
    $cek_email = mysqli_query($koneksi, "SELECT id FROM users WHERE email = '$email'");
    if(mysqli_num_rows($cek_email) > 0){
        $_SESSION['swal_msg'] = "Gagal! Email tersebut sudah terdaftar."; $_SESSION['swal_icon'] = "error";
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role_id, divisi_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssii", $nama, $email, $password, $role_id, $divisi_id);
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_msg'] = "Akun pengguna berhasil dibuat!"; $_SESSION['swal_icon'] = "success";
        }
    }
    header("Location: kelola_user.php"); exit();
}

if (isset($_POST['edit_user'])) {
    $id = $_POST['id_user'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role_id = $_POST['role_id'];
    
    $q_role = mysqli_query($koneksi, "SELECT nama_role FROM roles WHERE id = '$role_id'");
    $nama_role = mysqli_fetch_assoc($q_role)['nama_role'];
    
    // CATATAN VERSI "1 MANAGER UNTUK SEMUA DIVISI":
    // Hanya SPV yang wajib punya divisi. Kalau role diubah jadi Manager (atau role
    // lain selain SPV), divisi_id otomatis dikosongkan (NULL) di sini.
    $divisi_id = NULL;
    if (strtolower($nama_role) === 'spv') {
        $divisi_id = !empty($_POST['divisi_id']) ? $_POST['divisi_id'] : NULL;
    }

    if(!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, password=?, role_id=?, divisi_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssiii", $nama, $email, $password, $role_id, $divisi_id, $id);
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, role_id=?, divisi_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssiii", $nama, $email, $role_id, $divisi_id, $id);
    }

    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_msg'] = "Data pengguna berhasil diperbarui!"; $_SESSION['swal_icon'] = "success";
    }
    header("Location: kelola_user.php"); exit();
}

if (isset($_POST['hapus_user'])) {
    $id = $_POST['id_user'];
    if($id == $_SESSION['user_id']) {
        $_SESSION['swal_msg'] = "Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif!"; $_SESSION['swal_icon'] = "error";
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_msg'] = "Akun berhasil dihapus permanen."; $_SESSION['swal_icon'] = "success";
        }
    }
    header("Location: kelola_user.php"); exit();
}

// Ambil Referensi
$q_roles = mysqli_query($koneksi, "SELECT * FROM roles ORDER BY nama_role ASC");
$roles_arr = []; while($r = mysqli_fetch_assoc($q_roles)) $roles_arr[] = $r;

$q_divisi = mysqli_query($koneksi, "SELECT * FROM divisi ORDER BY nama_divisi ASC");
$div_arr = []; while($d = mysqli_fetch_assoc($q_divisi)) $div_arr[] = $d;

// Ambil Data User (JOIN)
$query_users = "SELECT u.*, r.nama_role, d.nama_divisi 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN divisi d ON u.divisi_id = d.id 
                ORDER BY r.nama_role ASC, u.nama ASC";
$result_users = mysqli_query($koneksi, $query_users);

require 'header.php';
?>

<style>
    .card-clean { background: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); animation: slideUp 0.4s ease forwards; }
    .card-header-clean { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    
    .table-modern thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; }
    .table-modern tbody td { padding: 15px 20px; font-size: 0.875rem; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    
    .btn-tambah { background: var(--lkpi-green); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; transition: 0.2s; }
    .btn-tambah:hover { background: #007031; transform: translateY(-1px); }

    .role-badge { padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.7rem; background: #e0e7ff; color: #4f46e5; text-transform: uppercase; }
    .divisi-badge { padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.7rem; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
</style>

<div class="card-clean">
    <div class="card-header-clean flex-wrap gap-3">
        <h5 class="m-0 fw-bold"><i class="fas fa-users-gear text-primary me-2"></i> Manajemen Akun Pengguna</h5>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="fas fa-user-plus me-1"></i> Buat Akun Baru</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Nama Lengkap & Email</th>
                    <th>Jabatan (Role)</th>
                    <th>Penempatan Divisi</th>
                    <th class="text-center pe-4" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result_users)) { ?>
                <tr>
                    <td class="ps-4">
                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['nama']); ?></strong>
                        <span class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($row['email']); ?></span>
                    </td>
                    <td><span class="role-badge"><?php echo htmlspecialchars($row['nama_role']); ?></span></td>
                    <td>
                        <?php if($row['nama_divisi']) { echo '<span class="divisi-badge">'.htmlspecialchars($row['nama_divisi']).'</span>'; } 
                              else { echo '<span class="text-muted fst-italic" style="font-size: 0.75rem;">Terpusat / All Div</span>'; } ?>
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-light border me-1 text-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)" data-bs-toggle="modal" data-bs-target="#modalEdit" title="Edit"><i class="fas fa-edit"></i></button>
                        
                        <?php if($row['id'] != $_SESSION['user_id']) { ?>
                        <form action="" method="POST" class="d-inline" id="formHapus-<?php echo $row['id']; ?>">
                            <input type="hidden" name="id_user" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="hapus_user" value="1">
                            <button type="button" class="btn btn-sm btn-light border text-danger" onclick="konfirmasiHapus(<?php echo $row['id']; ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="" method="POST">
                <div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="fw-bold">Buat Akun Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Nama lengkap staf...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Email Perusahaan</label>
                        <input type="email" name="email" class="form-control" required placeholder="nama@likuid.co.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Kata Sandi Awal</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passTambah" class="form-control border-end-0" required placeholder="Minimal 6 karakter" minlength="6">
                            <button class="btn btn-outline-secondary bg-transparent border-start-0 border" type="button" onclick="togglePassword('passTambah', 'eyeTambah')" title="Tampilkan/Sembunyikan Sandi" style="border-color: #cbd5e1;">
                                <i class="far fa-eye text-muted" id="eyeTambah"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Jabatan (Role)</label>
                        <select name="role_id" class="form-select" id="roleTambah" required onchange="cekTampilDivisi('roleTambah', 'wrapDivisiTambah', 'divisiTambah')">
                            <option value="" selected disabled>-- Pilih Role --</option>
                            <?php foreach($roles_arr as $r) { echo "<option value='{$r['id']}'>".strtoupper($r['nama_role'])."</option>"; } ?>
                        </select>
                    </div>

                    <!-- WRAPPER DIVISI (Sembunyi secara default, hanya muncul untuk role SPV) -->
                    <div class="mb-2" id="wrapDivisiTambah" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        <label class="form-label text-primary fw-bold" style="font-size: 0.85rem;"><i class="fas fa-network-wired me-1"></i> Alokasi Divisi</label>
                        <select name="divisi_id" class="form-select" id="divisiTambah">
                            <option value="" selected disabled>-- Pilih Divisi --</option>
                            <?php foreach($div_arr as $d) { echo "<option value='{$d['id']}'>{$d['nama_divisi']}</option>"; } ?>
                        </select>
                        <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">* Wajib untuk SPV.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-primary px-4">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="" method="POST">
                <div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="fw-bold">Edit Akun Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body px-4">
                    <input type="hidden" name="id_user" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Email Perusahaan</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Jabatan (Role)</label>
                        <select name="role_id" class="form-select" id="roleEdit" required onchange="cekTampilDivisi('roleEdit', 'wrapDivisiEdit', 'divisiEdit')">
                            <?php foreach($roles_arr as $r) { echo "<option value='{$r['id']}'>".strtoupper($r['nama_role'])."</option>"; } ?>
                        </select>
                    </div>

                    <div class="mb-3" id="wrapDivisiEdit" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        <label class="form-label text-primary fw-bold" style="font-size: 0.85rem;"><i class="fas fa-network-wired me-1"></i> Alokasi Divisi</label>
                        <select name="divisi_id" class="form-select" id="divisiEdit">
                            <option value="">-- Tanpa Divisi --</option>
                            <?php foreach($div_arr as $d) { echo "<option value='{$d['id']}'>{$d['nama_divisi']}</option>"; } ?>
                        </select>
                    </div>

                    <hr class="my-4">
                    <p class="text-danger fw-bold" style="font-size: 0.8rem; margin-bottom: 8px;"><i class="fas fa-lock me-1"></i> Ganti Password (Opsional)</p>
                    <div class="input-group">
                        <input type="password" name="password" id="passEdit" class="form-control border-end-0" placeholder="Biarkan kosong jika tidak ingin ganti">
                        <button class="btn btn-outline-secondary bg-transparent border-start-0 border" type="button" onclick="togglePassword('passEdit', 'eyeEdit')" title="Tampilkan/Sembunyikan Sandi" style="border-color: #cbd5e1;">
                            <i class="far fa-eye text-muted" id="eyeEdit"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-primary px-4">Update Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // FUNGSI JAVASCRIPT: Tampilkan/Sembunyikan Password
    function togglePassword(inputId, iconId) {
        let inputPass = document.getElementById(inputId);
        let iconEye = document.getElementById(iconId);
        
        if (inputPass.type === "password") {
            inputPass.type = "text";
            iconEye.classList.remove("fa-eye");
            iconEye.classList.add("fa-eye-slash");
        } else {
            inputPass.type = "password";
            iconEye.classList.remove("fa-eye-slash");
            iconEye.classList.add("fa-eye");
        }
    }

    // FUNGSI JAVASCRIPT: Tampilkan/Sembunyikan Kolom Divisi
    // CATATAN VERSI "1 MANAGER UNTUK SEMUA DIVISI":
    // Hanya role SPV yang butuh alokasi divisi. Manager sekarang general/terpusat,
    // sama seperti Finance, Dirut, dan Dirkeu.
    function cekTampilDivisi(selectRoleID, wrapperDivID, selectDivID) {
        let selectObj = document.getElementById(selectRoleID);
        // Mengambil teks yang sedang dipilih (contoh: "SPV" atau "MANAGER")
        let roleText = selectObj.options[selectObj.selectedIndex].text.toLowerCase();
        
        let wrapper = document.getElementById(wrapperDivID);
        let inputDivisi = document.getElementById(selectDivID);

        // Hanya tampilkan wrapper divisi kalau role-nya SPV
        if (roleText === 'spv') {
            wrapper.style.display = 'block';
            inputDivisi.required = true;
        } else {
            wrapper.style.display = 'none';
            inputDivisi.required = false;
            inputDivisi.value = ''; // Reset nilai divisi jika bukan SPV
        }
    }

    // Fungsi saat tombol Edit ditekan
    function editUser(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nama').value = data.nama;
        document.getElementById('edit_email').value = data.email;
        document.getElementById('roleEdit').value = data.role_id;
        
        // Cek secara paksa fungsi dropdown divisi
        cekTampilDivisi('roleEdit', 'wrapDivisiEdit', 'divisiEdit');
        
        // Pasang value divisinya setelah dipastikan terlihat (hanya relevan untuk SPV)
        if(data.divisi_id !== null) {
            document.getElementById('divisiEdit').value = data.divisi_id;
        }
    }

    function konfirmasiHapus(id) {
        Swal.fire({ title: 'Hapus Akun?', text: "Semua data terkait akun ini bisa terdampak.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => { if (result.isConfirmed) document.getElementById('formHapus-' + id).submit(); })
    }
</script>

<?php 
if(isset($_SESSION['swal_msg'])) { ?>
    <script> document.addEventListener('DOMContentLoaded', function() { Swal.fire({ title: 'Informasi', text: '<?php echo $_SESSION['swal_msg']; ?>', icon: '<?php echo $_SESSION['swal_icon']; ?>', confirmButtonColor: '#008a3d' }); }); </script>
<?php unset($_SESSION['swal_msg']); unset($_SESSION['swal_icon']); } ?>

<?php require 'footer.php'; ?>