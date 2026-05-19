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

        // Konversi notifikasi success menjadi Toast di pojok kanan atas & auto-hide
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach((alert, index) => {
                // Jika ada lebih dari satu alert, beri jarak agar tidak menumpuk sempurna
                const topOffset = 50 + (index * 15);
                
                // Pindahkan ke body dan ubah styling menjadi fixed top-right
                document.body.appendChild(alert);
                Object.assign(alert.style, {
                    position: 'fixed',
                    top: topOffset + 'px',
                    right: '8px',
                    zIndex: '9999',
                    minWidth: '280px',
                    boxShadow: '0 0.5rem 1rem rgba(0,0,0,0.15)',
                    transition: 'opacity 0.5s ease',
                    opacity: '1',
                    transform: 'scale(0.9)',
                    transformOrigin: 'top right'
                });
                
                // Auto hide setelah 3 detik HANYA jika itu dismissible
                if (alert.classList.contains('alert-dismissible')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    }, 3000);
                }
            });
        }, 100); // delay sedikit agar render awal selesai
    });
</script>
</body>
</html>