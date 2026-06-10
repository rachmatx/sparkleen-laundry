/**
 * Live Search / Filter - Sparkleen Laundry
 * Mengirim request AJAX saat user mengetik (debounce 400ms)
 * Mengupdate tbody tabel dan info pagination tanpa reload halaman.
 */
(function () {
    'use strict';

    /**
     * Inisialisasi live search pada sebuah input.
     * @param {HTMLInputElement} input  - elemen input pencarian
     * @param {string}           url   - endpoint AJAX (misal /pelanggan/ajaxSearch)
     * @param {Function}         renderFn - fungsi(data) → string HTML untuk tbody
     * @param {string}           tbodySelector - CSS selector tbody target
     * @param {string}           infoSelector  - CSS selector elemen info hasil (opsional)
     */
    function initLiveSearch(input, url, renderFn, tbodySelector, infoSelector) {
        if (!input) return;

        let debounceTimer = null;
        const tbody = document.querySelector(tbodySelector);
        const infoEl = infoSelector ? document.querySelector(infoSelector) : null;
        const paginationEl = document.querySelector('.live-search-pagination');

        // Simpan params tambahan dari data-* attribute pada input
        function getExtraParams() {
            const params = {};
            Object.keys(input.dataset).forEach(function (key) {
                if (key !== 'liveSearchUrl' && key !== 'liveSearchTarget') {
                    params[key] = input.dataset[key];
                }
            });
            return params;
        }

        function doSearch(keyword) {
            if (!tbody) return;

            // Spinner pada icon search
            const iconEl = document.getElementById('liveSearchIcon');
            if (iconEl) {
                iconEl.className = 'fas fa-spinner fa-spin text-muted';
            }

            // Tampilkan loading spinner di tbody
            const colCount = tbody.closest('table').querySelectorAll('thead th').length;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colCount}" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Mencari...
                    </td>
                </tr>`;

            const params = Object.assign({ q: keyword }, getExtraParams());
            const queryString = new URLSearchParams(params).toString();

            fetch(url + '?' + queryString, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    tbody.innerHTML = renderFn(data);

                    // Kembalikan icon search
                    if (iconEl) iconEl.className = 'fas fa-search text-muted';

                    // Update info jumlah hasil
                    if (infoEl) {
                        infoEl.textContent = keyword.trim() !== '' && data.total !== undefined
                            ? data.total + ' hasil'
                            : '';
                    }

                    // Sembunyikan pagination saat live search aktif
                    if (paginationEl) {
                        paginationEl.style.display = keyword.trim() !== '' ? 'none' : '';
                    }

                    // Re-bind quick view buttons jika ada (halaman transaksi)
                    if (typeof bindQuickView === 'function') bindQuickView();
                })
                .catch(function (err) {
                    console.error('Live search error:', err);
                    if (iconEl) iconEl.className = 'fas fa-search text-danger';
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="${colCount}" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Gagal memuat data. Silakan coba lagi.
                            </td>
                        </tr>`;
                });
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const keyword = this.value;
            debounceTimer = setTimeout(function () {
                doSearch(keyword);
            }, 400);
        });

        // Cegah submit form saat tekan Enter (biarkan live search yang bekerja)
        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
            });
        }

        // SORTING LOGIC
        const sortableHeaders = tbody.closest('table').querySelectorAll('th[data-sort]');
        sortableHeaders.forEach(th => {
            th.style.cursor = 'pointer';
            th.classList.add('user-select-none');
            
            // Add initial icon if not present
            if (!th.querySelector('i.fa-sort') && !th.querySelector('i.fa-sort-up') && !th.querySelector('i.fa-sort-down')) {
                th.innerHTML += ' <i class="fas fa-sort text-muted ms-1 opacity-25"></i>';
            }
            
            th.addEventListener('click', function() {
                const sortField = this.dataset.sort;
                let currentDir = input.dataset.dir || 'DESC';
                let currentSort = input.dataset.sort || '';
                
                // Toggle direction
                if (currentSort === sortField) {
                    currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentDir = 'ASC'; // Default on first click
                }
                
                input.dataset.sort = sortField;
                input.dataset.dir = currentDir;
                
                // Update icons
                sortableHeaders.forEach(h => {
                    const icon = h.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-sort text-muted ms-1 opacity-25';
                    }
                });
                
                const myIcon = this.querySelector('i');
                if (myIcon) {
                    myIcon.className = currentDir === 'ASC' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
                }
                
                // Trigger search immediately
                clearTimeout(debounceTimer);
                doSearch(input.value);
            });
        });
    }

    // =========================================================
    // RENDER FUNCTIONS — satu per halaman
    // =========================================================

    /* ---------- PELANGGAN ---------- */
    function renderPelanggan(data) {
        if (!data.data || data.data.length === 0) {
            return `<tr>
                <td colspan="7" class="text-center text-muted py-5">
                    <i class="fas fa-users fa-2x d-block mb-2 opacity-25"></i>
                    Tidak ada data yang cocok.
                </td>
            </tr>`;
        }
        let html = '';
        let no = 1;
        data.data.forEach(function (p) {
            const statusBadge = p.is_aktif == 1
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>';

            let aksiHtml = '';
            if (data.canEdit) {
                aksiHtml += `<a href="/pelanggan/edit/${p.id_pelanggan}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i> Edit</a> `;
            }
            if (data.canToggle) {
                if (p.is_aktif == 1) {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin menonaktifkan pelanggan ini?')) window.location.href='/pelanggan/toggle/${p.id_pelanggan}'" class="btn btn-danger btn-sm"><i class="fas fa-power-off me-1"></i> Nonaktif</a>`;
                } else {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin mengaktifkan pelanggan ini?')) window.location.href='/pelanggan/toggle/${p.id_pelanggan}'" class="btn btn-success btn-sm"><i class="fas fa-power-off me-1"></i> Aktifkan</a>`;
                }
            }

            html += `<tr>
                <td class="text-center">${no++}</td>
                <td><code>${escHtml(p.kode_pelanggan)}</code></td>
                <td>${escHtml(p.nama_pelanggan)}</td>
                <td>${escHtml(p.no_telp)}</td>
                <td>${escHtml(p.alamat || '-')}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">${aksiHtml || '<span class="text-muted small">-</span>'}</td>
            </tr>`;
        });
        return html;
    }

    /* ---------- KARYAWAN ---------- */
    function renderKaryawan(data) {
        if (!data.data || data.data.length === 0) {
            return `<tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Tidak ada data yang cocok.
                </td>
            </tr>`;
        }
        let html = '';
        let no = 1;
        data.data.forEach(function (k) {
            const levelClass = k.level === 'pemilik' ? 'text-bg-primary'
                : (k.level === 'admin' ? 'text-bg-success' : 'text-bg-secondary');
            const statusBadge = k.is_aktif == 1
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>';

            let aksiHtml = '';
            if (data.canManage && data.canManage.includes(String(k.id_karyawan))) {
                if (k.is_aktif == 1) {
                    aksiHtml = `<a href="/karyawan/edit/${k.id_karyawan}" class="btn btn-warning btn-sm text-dark me-1"><i class="fas fa-edit"></i> Edit</a>
                        <a href="#" onclick="if(confirm('Yakin ingin menonaktifkan karyawan ini?')) window.location.href='/karyawan/toggle/${k.id_karyawan}'" class="btn btn-danger btn-sm"><i class="fas fa-power-off"></i> Nonaktif</a>`;
                } else {
                    aksiHtml = `<a href="/karyawan/edit/${k.id_karyawan}" class="btn btn-warning btn-sm text-dark me-1"><i class="fas fa-edit"></i> Edit</a>
                        <a href="#" onclick="if(confirm('Yakin ingin mengaktifkan karyawan ini?')) window.location.href='/karyawan/toggle/${k.id_karyawan}'" class="btn btn-success btn-sm"><i class="fas fa-power-off"></i> Aktifkan</a>`;
                }
            } else {
                aksiHtml = '<span class="text-muted"><i class="fas fa-lock"></i></span>';
            }

            html += `<tr>
                <td class="text-center">${no++}</td>
                <td>${escHtml(k.nama_karyawan)}</td>
                <td>${escHtml(k.username)}</td>
                <td><span class="badge ${levelClass}">${capitalize(k.level)}</span></td>
                <td>${escHtml(k.no_telp)}</td>
                <td>Rp ${formatNumber(k.gaji_pokok)}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">${aksiHtml}</td>
            </tr>`;
        });
        return html;
    }

    /* ---------- LAYANAN ---------- */
    function renderLayanan(data) {
        if (!data.data || data.data.length === 0) {
            return `<tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="fas fa-soap fa-2x d-block mb-2 opacity-25"></i>
                    Tidak ada data yang cocok.
                </td>
            </tr>`;
        }
        let html = '';
        let no = 1;
        data.data.forEach(function (l) {
            const statusBadge = l.is_aktif == 1
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>';

            let aksiHtml = '';
            if (data.canManage) {
                if (data.isAdmin) {
                    aksiHtml += `<a href="/layanan/resep/${l.id_layanan}" class="btn btn-info btn-sm text-white me-1"><i class="fas fa-flask me-1"></i> Resep</a>`;
                }
                aksiHtml += `<a href="/layanan/edit/${l.id_layanan}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i> Edit</a> `;
                if (l.is_aktif == 1) {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin menonaktifkan layanan ini?')) window.location.href='/layanan/toggle/${l.id_layanan}'" class="btn btn-danger btn-sm"><i class="fas fa-power-off me-1"></i> Nonaktif</a>`;
                } else {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin mengaktifkan layanan ini?')) window.location.href='/layanan/toggle/${l.id_layanan}'" class="btn btn-success btn-sm"><i class="fas fa-power-off me-1"></i> Aktifkan</a>`;
                }
            }

            html += `<tr>
                <td class="text-center">${no++}</td>
                <td>${escHtml(l.nama_layanan)}</td>
                <td>Rp ${formatNumber(l.harga_per_kg)} / kg</td>
                <td><span class="badge text-bg-secondary">${l.estimasi_hari} hari</span></td>
                <td class="text-center">${statusBadge}</td>
                ${data.canManage ? `<td class="text-center">${aksiHtml}</td>` : ''}
            </tr>`;
        });
        return html;
    }

    /* ---------- INVENTARIS ---------- */
    function renderInventaris(data) {
        if (!data.data || data.data.length === 0) {
            return `<tr>
                <td colspan="9" class="text-center text-muted py-5">
                    <i class="fas fa-boxes fa-2x d-block mb-2 opacity-25"></i>
                    Tidak ada data yang cocok.
                </td>
            </tr>`;
        }
        let html = '';
        let no = 1;
        data.data.forEach(function (inv) {
            const kritis = parseFloat(inv.stok) <= parseFloat(inv.stok_minimal);
            const hampirHabis = !kritis && parseFloat(inv.stok) <= parseFloat(inv.stok_minimal) * 1.5;

            const stokBadge = kritis
                ? '<span class="badge text-bg-danger">Stok Kritis</span>'
                : (hampirHabis ? '<span class="badge text-bg-warning text-dark">Hampir Habis</span>'
                    : '<span class="badge text-bg-success">Aman</span>');

            const statusBadge = inv.is_aktif == 1
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>';

            const kritisBadge = kritis
                ? '<span class="badge text-bg-danger ms-1"><i class="fas fa-exclamation-triangle"></i> Kritis</span>'
                : '';

            let aksiHtml = '';
            if (data.canManage) {
                aksiHtml += `<a href="/inventaris/edit/${inv.id_inventaris}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i> Edit</a> `;
                if (inv.is_aktif == 1) {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin menonaktifkan barang ini?')) window.location.href='/inventaris/toggle/${inv.id_inventaris}'" class="btn btn-danger btn-sm"><i class="fas fa-power-off me-1"></i> Nonaktif</a>`;
                } else {
                    aksiHtml += `<a href="#" onclick="if(confirm('Yakin ingin mengaktifkan barang ini?')) window.location.href='/inventaris/toggle/${inv.id_inventaris}'" class="btn btn-success btn-sm"><i class="fas fa-power-off me-1"></i> Aktifkan</a>`;
                }
            } else {
                aksiHtml = '<span class="text-muted small">-</span>';
            }

            html += `<tr class="${kritis ? 'table-danger' : ''}">
                <td class="text-center">${no++}</td>
                <td class="text-center">${escHtml(inv.nama_barang)}${kritisBadge}</td>
                <td class="text-center fw-bold ${kritis ? 'text-danger' : ''}">${parseFloat(inv.stok).toFixed(2)}</td>
                <td class="text-center">${escHtml(inv.satuan)}</td>
                <td class="text-center">Rp ${formatNumber(inv.harga_beli)}</td>
                <td class="text-center">${parseFloat(inv.stok_minimal).toFixed(2)} ${escHtml(inv.satuan)}</td>
                <td class="text-center">${stokBadge}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">${aksiHtml}</td>
            </tr>`;
        });
        return html;
    }

    /* ---------- TRANSAKSI ---------- */
    function renderTransaksi(data) {
        if (!data.data || data.data.length === 0) {
            return `<tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    Tidak ada data yang cocok.
                </td>
            </tr>`;
        }
        let html = '';
        data.data.forEach(function (t) {
            const statusClass = {
                menunggu: 'text-bg-warning',
                proses:   'text-bg-info',
                setrika:  'text-bg-secondary',
                selesai:  'text-bg-success',
                diambil:  'text-bg-primary',
            }[t.status] || 'text-bg-dark';

            const bayarClass = t.status_pembayaran === 'lunas' ? 'text-bg-success'
                : (t.status_pembayaran === 'cicil' ? 'text-bg-warning' : 'text-bg-danger');

            let aksiHtml = `
                <div class="btn-group btn-group-sm">
                    <a href="/transaksi/detail/${t.id_transaksi}" class="btn btn-info text-white btn-quick-detail" data-id="${t.id_transaksi}" title="Quick View">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="/transaksi/nota/${t.id_transaksi}" target="_blank" class="btn btn-secondary" title="Cetak Nota">
                        <i class="fas fa-print"></i>
                    </a>`;
            if (t.status !== 'diambil' && data.canEdit) {
                aksiHtml += `<a href="/transaksi/ubah-status/${t.id_transaksi}" class="btn btn-warning text-dark" title="Update Status"><i class="fas fa-exchange-alt"></i></a>`;
            }
            aksiHtml += '</div>';

            html += `<tr>
                <td><code>${escHtml(t.no_nota)}</code></td>
                <td>${escHtml(t.nama_pelanggan)}</td>
                <td>${escHtml(t.tgl_masuk)}</td>
                <td class="text-center">${t.total_berat} kg</td>
                <td class="text-end">Rp ${formatNumber(t.grand_total)}</td>
                <td class="text-center"><span class="badge ${statusClass}">${capitalize(t.status)}</span></td>
                <td class="text-center"><span class="badge ${bayarClass}">${capitalize(t.status_pembayaran)}</span></td>
                <td class="text-center">${aksiHtml}</td>
            </tr>`;
        });
        return html;
    }

    // =========================================================
    // HELPERS
    // =========================================================
    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(num) {
        return parseInt(num || 0).toLocaleString('id-ID');
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // =========================================================
    // AUTO-INIT berdasarkan data-live-search-* attribute
    // =========================================================
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('liveSearchInput');
        if (!input) return;

        const page = input.dataset.page;
        let renderFn = null;
        let url = null;

        switch (page) {
            case 'pelanggan':
                url = '/pelanggan/ajaxSearchTable';
                renderFn = renderPelanggan;
                break;
            case 'karyawan':
                url = '/karyawan/ajaxSearch';
                renderFn = renderKaryawan;
                break;
            case 'layanan':
                url = '/layanan/ajaxSearch';
                renderFn = renderLayanan;
                break;
            case 'inventaris':
                url = '/inventaris/ajaxSearch';
                renderFn = renderInventaris;
                break;
            case 'transaksi':
                url = '/transaksi/ajaxSearch';
                renderFn = renderTransaksi;
                break;
        }

        if (url && renderFn) {
            initLiveSearch(input, url, renderFn, '#liveSearchTbody', '#liveSearchInfo');
        }
    });

})();
