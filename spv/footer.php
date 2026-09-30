</div> 
    <!-- MAIN CONTENT END -->

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk Toggle Sidebar di HP -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggleMenu = document.getElementById('btnToggleMenu');
            const sidebarMenu = document.getElementById('sidebarMenu');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (btnToggleMenu && sidebarMenu && sidebarOverlay) {
                // Saat tombol garis 3 diklik
                btnToggleMenu.addEventListener('click', function() {
                    sidebarMenu.classList.add('show');
                    sidebarOverlay.classList.add('show');
                });

                // Saat area gelap di luar menu diklik (tutup menu)
                sidebarOverlay.addEventListener('click', function() {
                    sidebarMenu.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }
        });
    </script>

    <!-- Global Script untuk mengubah pesan validasi form default browser ke Bahasa Indonesia -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var elements = document.getElementsByTagName("INPUT");
            for (var i = 0; i < elements.length; i++) {
                elements[i].oninvalid = function(e) {
                    e.target.setCustomValidity("");
                    if (!e.target.validity.valid) {
                        switch(e.srcElement.type) {
                            case "email":
                                e.target.setCustomValidity("Format email salah. Pastikan menggunakan tanda @");
                                break;
                            case "number":
                                e.target.setCustomValidity("Harap masukkan angka yang valid");
                                break;
                            case "file":
                                e.target.setCustomValidity("Harap unggah dokumen yang diperlukan");
                                break;
                            default:
                                e.target.setCustomValidity("Kolom ini wajib diisi");
                        }
                    }
                };
                elements[i].oninput = function(e) {
                    e.target.setCustomValidity("");
                };
            }
            
            var textareas = document.getElementsByTagName("TEXTAREA");
            for (var i = 0; i < textareas.length; i++) {
                textareas[i].oninvalid = function(e) {
                    e.target.setCustomValidity("Kolom keterangan wajib diisi");
                };
                textareas[i].oninput = function(e) {
                    e.target.setCustomValidity("");
                };
            }
        });

        // Fungsi Global untuk Konfirmasi Logout menggunakan SweetAlert2
        function konfirmasiLogout(url) {
            Swal.fire({
                title: 'Keluar Sistem?',
                text: "Anda akan mengakhiri sesi dan harus login kembali.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Keluar!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>