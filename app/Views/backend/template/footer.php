        </div>
    </div>
</main>

<!-- Footer -->
<footer class="app-footer">
    <strong>&copy; <?= date('Y') ?> Sparkleen Laundry.</strong>
</footer>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="/adminlte4/dist/js/adminlte.js"></script>
<script src="/js/password-toggle.js"></script>
<script src="/js/live-search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                },
            });
        }

        // Setup Live Clock di Top Bar
        function updateClock() {
            const clockEl = document.getElementById('topBarClock');
            if (clockEl) {
                const now = new Date();
                // Opsi timezone tidak diset = menggunakan waktu lokal device (negara/wilayah user)
                clockEl.textContent = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                }).replace(/\./g, ':');
            }
        }
        setInterval(updateClock, 1000);
        updateClock(); // Jalankan sekali agar tidak menunggu 1 detik

        // Konversi notifikasi Bootstrap menjadi SweetAlert2 Toast (Point 4)
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            showCloseButton: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Tangkap alert dari Flashdata (Success)
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            let msg = alert.innerText.trim();
            alert.style.display = 'none'; // Sembunyikan elemen bawaan agar tidak menggeser layout
            Toast.fire({
                icon: 'success',
                title: msg
            });
        });

        // Tangkap alert dari Flashdata (Error/Danger)
        const errorAlerts = document.querySelectorAll('.alert-danger');
        errorAlerts.forEach(alert => {
            let msg = alert.innerText.trim();
            // Kecuali validasi form (jangan disembunyikan jika itu list ul li error validasi)
            if(!alert.querySelector('ul')) {
                alert.style.display = 'none';
                Toast.fire({
                    icon: 'error',
                    title: msg
                });
            }
        });

        // Anti Double-Click / Loading State
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    // Simpan teks/HTML asli ke data-attribute
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
                }
            });
        });

        // Mengatasi masalah Back-Forward Cache (BFCache)
        // Saat user menekan tombol 'Back' di browser, halaman bisa jadi diload dari cache
        window.addEventListener('pageshow', function(event) {
            // event.persisted bernilai true jika halaman dimuat dari cache (seperti saat klik tombol back browser)
            if (event.persisted) {
                const submitButtons = document.querySelectorAll('button[type="submit"]');
                submitButtons.forEach(btn => {
                    if (btn.disabled && btn.dataset.originalHtml) {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.originalHtml;
                    }
                });
            }
        });
        
        // Cek Stok Kritis secara AJAX (jika elemen lonceng ada)
        const notifContainer = document.getElementById('notif-stok-container');
        const btnStokNotif = document.getElementById('btn-stok-notif');
        if (notifContainer) {
            fetch('/inventaris/ajaxCekStokKritis', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    notifContainer.style.display = 'block';
                    document.getElementById('notif-stok-badge').textContent = data.count;
                    
                    // Isi toast body
                    let detailHTML = 'Barang menipis:<ul>';
                    data.items.slice(0, 3).forEach(item => {
                        detailHTML += `<li>${item.nama} (Sisa ${item.stok})</li>`;
                    });
                    if (data.count > 3) detailHTML += `<li>dan ${data.count - 3} lainnya...</li>`;
                    detailHTML += '</ul>';
                    document.getElementById('toast-stok-body').innerHTML = detailHTML;
                    
                    // Tampilkan toast KETIKA lonceng diklik (bukan otomatis)
                    if (btnStokNotif) {
                        btnStokNotif.addEventListener('click', function(e) {
                            e.preventDefault();
                            const toastEl = document.getElementById('toastStok');
                            const toast = new bootstrap.Toast(toastEl, { autohide: false });
                            toast.show();
                        });
                    }
                }
            })
            .catch(err => console.error('Gagal memuat notifikasi stok:', err));
        }
    });
</script>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;">
  <div id="toastStok" class="toast text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header text-dark">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong class="me-auto">Peringatan Stok</strong>
      <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toast-stok-body">
      <!-- Diisi oleh JS -->
    </div>
    <?php if (in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
    <div class="p-2 border-top border-dark border-opacity-10 text-end">
        <a href="/inventaris/warning" class="btn btn-sm btn-dark">Lihat Detail & Restok</a>
    </div>
    <?php else: ?>
    <div class="p-2 border-top border-dark border-opacity-10 text-end">
        <small class="text-dark fw-bold">Harap lapor ke Admin</small>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>