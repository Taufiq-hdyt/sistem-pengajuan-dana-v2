<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah SPV
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'spv') {
    header("Location: ../index.php");
    exit();
}

$user_id_aktif = $_SESSION['user_id'];

require 'header.php';
?>

<style>
    .table-custom th {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 12px;
    }
    .table-custom td {
        vertical-align: middle;
        padding-top: 1.2rem;
        padding-bottom: 1.2rem;
        border-bottom: 1px solid #f8f9fa;
    }
    .badge-soft-warning {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: #f0f9ff;
        color: #0284c7;
        border: 1px solid #bae6fd;
        font-weight: 600;
    }
    .badge-soft-success {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        font-weight: 600;
    }
    .btn-outline-custom {
        color: #64748b;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        font-weight: 500;
        font-size: 0.85rem;
    }
    .btn-outline-custom:hover {
        background-color: #f8fafc;
        color: #334155;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0" style="color: #1e293b; font-size: 1.25rem;">
                    <i class="fas fa-receipt text-primary me-2"></i> Laporan Pertanggungjawaban
                </h4>
            </div>

            <p class="text-muted" style="font-size: 0.875rem;">
                Daftar di bawah ini menampilkan pengajuan Anda yang sudah <strong>Disetujui Final</strong> dan
                <strong>sudah ditransfer oleh Finance</strong>. Klik "Kelola Laporan" untuk merincikan pembelian dan mengunggah bukti nota.
            </p>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase">Vendor & Nominal</th>
                            <th class="text-uppercase text-center">Tgl Ditransfer</th>
                            <th class="text-uppercase text-center">Status Laporan</th>
                            <th class="text-uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Hanya tampilkan pengajuan yang sudah Disetujui Final DAN sudah ditransfer Finance
                        $query = "SELECT * FROM pengajuan 
                                  WHERE user_id = ? 
                                  AND status_dirkeu = 'Disetujui' 
                                  AND bukti_transfer IS NOT NULL AND bukti_transfer != ''
                                  ORDER BY id DESC";
                        $stmt = $koneksi->prepare($query);
                        $stmt->bind_param("i", $user_id_aktif);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $nominal        = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                                $status_laporan = strtolower(trim($row['status_laporan'] ?? ''));
                                $tgl_transfer   = !empty($row['tanggal_transfer']) ? date('d M Y', strtotime($row['tanggal_transfer'])) : '-';

                                // Badge Status Laporan
                                if ($status_laporan == 'valid') {
                                    $badge_laporan = '<span class="badge rounded-pill badge-soft-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Pengajuan Selesai</span>';
                                } elseif ($status_laporan == 'revisi') {
                                    $badge_laporan = '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-exclamation-circle me-1"></i> Revisi Laporan</span>';
                                } elseif ($status_laporan == 'menunggu validasi') {
                                    $badge_laporan = '<span class="badge rounded-pill badge-soft-info px-3 py-2"><i class="fas fa-spinner fa-pulse me-1"></i> Menunggu Validasi</span>';
                                } else {
                                    $badge_laporan = '<span class="badge rounded-pill badge-soft-warning px-3 py-2"><i class="fas fa-file-circle-exclamation me-1"></i> Belum Ada Laporan</span>';
                                }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 1rem;"><?= htmlspecialchars($row['nama_vendor']); ?></div>
                                <div class="text-success fw-bold" style="font-size: 0.9rem;"><?= $nominal; ?></div>
                            </td>
                            <td class="text-center align-middle text-dark"><?= $tgl_transfer; ?></td>
                            <td class="text-center align-middle"><?= $badge_laporan; ?></td>
                            <td class="text-center align-middle">
                                <?php if ($status_laporan == 'valid'): ?>
                                    <span class="text-muted fst-italic" style="font-size: 0.8rem;">Tidak ada tindakan</span>
                                <?php else: ?>
                                    <a href="laporan_pengajuan.php?id=<?= $row['id']; ?>" class="btn btn-outline-custom rounded-pill px-3 py-1">
                                        <i class="fas fa-cloud-upload-alt me-1"></i> Kelola Laporan
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 mb-3 d-block text-light"></i>
                                Belum ada pengajuan yang sudah ditransfer Finance.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if(typeof Swal !== 'undefined'){
                Swal.fire({
                    title: 'Informasi',
                    text: <?php echo json_encode($_SESSION['swal_msg']); ?>,
                    icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
                    confirmButtonColor: '#008a3d'
                });
            }
        });
    </script>
<?php
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_icon']);
endif;
?>

<?php require 'footer.php'; ?>