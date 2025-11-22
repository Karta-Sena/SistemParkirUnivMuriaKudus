/**
 * Dashboard Petugas - JavaScript 2088
 * File: dashboard_petugas.js
 *
 * Fitur utama:
 * - Sidebar collapse (desktop)
 * - Toast auto-hide
 * - Animasi kartu
 * - Smart Search:
 *   - Input utama
 *   - Clear button
 *   - Chip filter
 *   - Panel filter lanjut
 *   - Dummy export, refresh
 *   - State dan status label (Idle / Searching / Error)
 * - Struktur siap untuk integrasi AJAX / API backend
 */

// ================================
// GLOBAL STATE
// ================================

const DashboardState = {
    search: {
        query: "",
        filters: {
            jenis: "",
            status: "",
            slot: "",
            start: "",
            end: "",
            sort: "waktu_desc",
            chips: []
        },
        isSearching: false,
        lastResults: [],
        lastError: null
    }
};

// Util: debounce sederhana untuk pencarian
function debounce(fn, delay) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ================================
// INIT ROOT
// ================================

document.addEventListener("DOMContentLoaded", () => {
    initSidebar();
    initToast();
    initAnimations();
    initSmartSearch();
});

// ================================
// SIDEBAR
// ================================

function initSidebar() {
    const sidebar = document.getElementById("sidebar");
    const sidebarToggleBtn = document.getElementById("sidebarToggleBtn");
    
    if (!sidebar || !sidebarToggleBtn) return;

    // 1. Fungsi Toggle
    sidebarToggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");
        
        // Simpan status ke LocalStorage agar browser ingat
        const isCollapsed = sidebar.classList.contains("collapsed");
        localStorage.setItem("sidebarCollapsed", isCollapsed ? "true" : "false");
    });

    // 2. Load Status Tersimpan (Saat Refresh)
    const savedState = localStorage.getItem("sidebarCollapsed");
    
    // Jika di desktop (> 768px) dan status tersimpan adalah 'true', otomatis tutup
    if (window.innerWidth > 768 && savedState === "true") {
        sidebar.classList.add("collapsed");
    }
}

// ================================
// TOAST
// ================================

function initToast() {
    const toast = document.getElementById("toast");
    if (!toast) return;

    setTimeout(() => {
        toast.style.transition = "all 0.3s ease";
        toast.style.opacity = "0";
        toast.style.transform = "translateX(20px)";
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 320);
    }, 4000);
}

// ================================
// ANIMASI DASAR
// ================================

function initAnimations() {
    const cards = document.querySelectorAll(
        ".stat-card, .action-card, .activity-section, .search-section"
    );

    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(16px)";
        setTimeout(() => {
            card.style.transition = "all 0.4s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, 80 * index);
    });
}

// ================================
// SMART SEARCH 2088
// ================================

function initSmartSearch() {
    const input = document.getElementById("searchInput");
    const btnSearch = document.getElementById("searchBtn");
    const btnClear = document.getElementById("searchClearBtn");
    const btnAdvancedToggle = document.getElementById("searchAdvancedToggle");
    const advancedPanel = document.getElementById("searchAdvancedPanel");
    const btnResetFilters = document.getElementById("searchResetFilters");
    const chips = document.querySelectorAll(".chip-filter");
    const filterJenis = document.getElementById("filterJenis");
    const filterStatus = document.getElementById("filterStatus");
    const filterSlot = document.getElementById("filterSlot");
    const filterStart = document.getElementById("filterStart");
    const filterEnd = document.getElementById("filterEnd");
    const filterSort = document.getElementById("filterSort");
    const btnExport = document.getElementById("searchExportBtn");
    const btnRefresh = document.getElementById("searchRefreshBtn");
    const resultBody = document.getElementById("searchResultBody");
    const resultCount = document.getElementById("searchResultCount");
    const statusLabel = document.getElementById("searchStatusLabel");

    if (!input || !btnSearch || !resultBody) {
        return;
    }

    // Helper untuk update status label
    function setSearchStatus(status, message) {
        DashboardState.search.isSearching = status === "searching";
        DashboardState.search.lastError = status === "error" ? message : null;

        if (!statusLabel) return;

        statusLabel.classList.remove("idle", "searching", "error");

        if (status === "idle") {
            statusLabel.textContent = "Idle";
            statusLabel.classList.add("idle");
        } else if (status === "searching") {
            statusLabel.textContent = "Mencari...";
            statusLabel.classList.add("searching");
        } else if (status === "error") {
            statusLabel.textContent = message || "Error";
            statusLabel.classList.add("error");
        }
    }

    // Helper update count
    function updateResultCount() {
        if (!resultCount) return;
        const len = DashboardState.search.lastResults.length;
        resultCount.textContent = len + " hasil";
    }

    // Render hasil dummy
    function renderResults(results) {
        if (!Array.isArray(results) || results.length === 0) {
            resultBody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Tidak ada data yang cocok dengan pencarian saat ini.</span>
                    </td>
                </tr>
            `;
            DashboardState.search.lastResults = [];
            updateResultCount();
            return;
        }

        DashboardState.search.lastResults = results;
        updateResultCount();

        const rows = results.map((row) => {
            const jenisLabel = row.jenis === "motor" ? "Motor" : (row.jenis === "mobil" ? "Mobil" : "-");
            const jenisIcon = row.jenis === "motor" ? "motorcycle" : "car";
            const statusClass = row.status ? row.status.toLowerCase() : "";
            const waktu = row.waktu || "-";

            return `
                <tr>
                    <td><span class="plat-nomor">${escapeHtml(row.plat || "")}</span></td>
                    <td>${escapeHtml(row.pemilik || "-")}</td>
                    <td>
                        <span class="vehicle-type ${statusClass}">
                            <i class="fa-solid fa-${jenisIcon}"></i>
                            ${jenisLabel}
                        </span>
                    </td>
                    <td>${escapeHtml(row.slot || "-")}</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${escapeHtml(capitalize(row.status || "-"))}
                        </span>
                    </td>
                    <td class="time-col">${escapeHtml(waktu)}</td>
                </tr>
            `;
        }).join("");

        resultBody.innerHTML = rows;
    }

    // Jalankan pencarian (dummy)
    const doSearch = debounce(() => {
        const q = DashboardState.search.query.trim();
        const filters = DashboardState.search.filters;

        // Jika input kosong dan tidak ada filter aktif, reset hasil
        if (q.length === 0 && !hasAnyFilter(filters)) {
            renderResults([]);
            setSearchStatus("idle");
            return;
        }

        setSearchStatus("searching");

        // 1. Susun Parameter untuk dikirim ke API
        const params = new URLSearchParams();
        
        // Parameter dasar
        if (q) params.append('q', q);
        if (filters.jenis) params.append('jenis', filters.jenis);
        if (filters.status) params.append('status', filters.status);
        if (filters.slot) params.append('slot', filters.slot);
        if (filters.start) params.append('start', filters.start);
        if (filters.end) params.append('end', filters.end);
        if (filters.sort) params.append('sort', filters.sort);

        // 2. Logika CHIP Filter (Tombol Cepat)
        if (filters.chips.includes('hari-ini')) {
            const today = new Date().toISOString().split('T')[0];
            params.append('start', today + ' 00:00:00');
            params.append('end', today + ' 23:59:59');
        }
        if (filters.chips.includes('sedang-parkir')) params.set('status', 'masuk');
        if (filters.chips.includes('motor')) params.set('jenis', 'motor');
        if (filters.chips.includes('mobil')) params.set('jenis', 'mobil');

        // 3. Panggil API (AJAX)
        fetch(`api_cari_parkir.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) throw new Error("Gagal menghubungi server");
                return response.json();
            })
            .then(json => {
                if (json.status === 'success') {
                    renderResults(json.data); // Tampilkan data asli
                    setSearchStatus("idle");
                } else {
                    throw new Error(json.message || "Terjadi kesalahan pada data");
                }
            })
            .catch(err => {
                console.error("Search Error:", err);
                setSearchStatus("error", "Gagal memuat data");
                
                // Tampilkan pesan error di tabel agar petugas tahu
                resultBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state" style="color: #EF4444;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>Koneksi Terputus: ${escapeHtml(err.message)}</span>
                        </td>
                    </tr>
                `;
            });

    }, 500);

    // Cek minimal satu filter
    function hasAnyFilter(filters) {
        return Boolean(
            filters.jenis || filters.status || filters.slot ||
            filters.start || filters.end || (filters.chips && filters.chips.length > 0)
        );
    }

    // Sync state dari input & filter
    function syncSearchStateFromUI() {
        DashboardState.search.query = input.value || "";

        DashboardState.search.filters.jenis  = filterJenis ? (filterJenis.value || "") : "";
        DashboardState.search.filters.status = filterStatus ? (filterStatus.value || "") : "";
        DashboardState.search.filters.slot   = filterSlot ? (filterSlot.value || "") : "";
        DashboardState.search.filters.start  = filterStart ? (filterStart.value || "") : "";
        DashboardState.search.filters.end    = filterEnd ? (filterEnd.value || "") : "";
        DashboardState.search.filters.sort   = filterSort ? (filterSort.value || "waktu_desc") : "waktu_desc";

        // chips
        const activeChips = [];
        chips.forEach(chip => {
            if (chip.classList.contains("active")) {
                activeChips.push(chip.dataset.chip);
            }
        });
        DashboardState.search.filters.chips = activeChips;
    }

    // Reset semua filter
    function resetFilters() {
        if (filterJenis) filterJenis.value = "";
        if (filterStatus) filterStatus.value = "";
        if (filterSlot) filterSlot.value = "";
        if (filterStart) filterStart.value = "";
        if (filterEnd) filterEnd.value = "";
        if (filterSort) filterSort.value = "waktu_desc";
        chips.forEach(chip => chip.classList.remove("active"));

        syncSearchStateFromUI();
        doSearch();
    }

    // Event binding
    input.addEventListener("input", () => {
        DashboardState.search.query = input.value;
        syncSearchStateFromUI();
        doSearch();
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            syncSearchStateFromUI();
            doSearch();
        }
    });

    if (btnSearch) {
        btnSearch.addEventListener("click", () => {
            syncSearchStateFromUI();
            doSearch();
        });
    }

    if (btnClear) {
        btnClear.addEventListener("click", () => {
            input.value = "";
            DashboardState.search.query = "";
            syncSearchStateFromUI();
            renderResults([]);
            setSearchStatus("idle");
        });
    }

    if (btnAdvancedToggle && advancedPanel) {
        btnAdvancedToggle.addEventListener("click", () => {
            const isOpen = advancedPanel.classList.toggle("open");
            if (isOpen) {
                btnAdvancedToggle.classList.add("active");
            } else {
                btnAdvancedToggle.classList.remove("active");
            }
        });
    }

    if (btnResetFilters) {
        btnResetFilters.addEventListener("click", () => {
            resetFilters();
        });
    }

    // Filter perubahan
    [filterJenis, filterStatus, filterSlot, filterStart, filterEnd, filterSort].forEach(el => {
        if (!el) return;
        el.addEventListener("change", () => {
            syncSearchStateFromUI();
            doSearch();
        });
    });

    // Chips logic
    chips.forEach(chip => {
        chip.addEventListener("click", () => {
            chip.classList.toggle("active");
            syncSearchStateFromUI();
            doSearch();
        });
    });

    // Export (dummy)
    if (btnExport) {
        btnExport.addEventListener("click", () => {
            const data = DashboardState.search.lastResults || [];
            if (data.length === 0) {
                // Gunakan Toast jika ada, atau alert biasa
                alert("Tidak ada data untuk diekspor saat ini.");
                return;
            }

            // 1. Buat Header CSV
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Plat Nomor,Pemilik,Jenis,Lokasi,Status,Waktu\n";

            // 2. Loop Data & Susun Baris
            data.forEach(row => {
                // Bersihkan data dari koma agar tidak merusak format CSV
                const plat    = (row.plat || "-").replace(/,/g, "");
                const pemilik = (row.pemilik || "-").replace(/,/g, "");
                const jenis   = (row.jenis || "-");
                const slot    = (row.slot || "-");
                const status  = (row.status || "-");
                const waktu   = (row.waktu || "-");

                csvContent += `${plat},${pemilik},${jenis},${slot},${status},${waktu}\n`;
            });

            // 3. Buat Link Download Otomatis
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            
            // Nama file dengan timestamp
            const dateStr = new Date().toISOString().slice(0,10);
            link.setAttribute("download", `Laporan_Parkir_${dateStr}.csv`);
            
            document.body.appendChild(link); // Diperlukan untuk Firefox
            link.click();
            document.body.removeChild(link);
        });
    }

    // Refresh (dummy)
    if (btnRefresh) {
        btnRefresh.addEventListener("click", () => {
            // Tambahkan efek visual loading pada tombol
            const originalIcon = btnRefresh.innerHTML;
            btnRefresh.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btnRefresh.disabled = true;

            // Panggil fungsi pencarian ulang
            syncSearchStateFromUI();
            doSearch();

            // Kembalikan tombol setelah jeda singkat (simulasi UX)
            setTimeout(() => {
                btnRefresh.innerHTML = originalIcon;
                btnRefresh.disabled = false;
            }, 800);
        });
    }

    // Inisialisasi awal
    setSearchStatus("idle");
    updateResultCount();
    renderResults([]);

// ================================
// UTIL
// ================================

// Escape HTML sederhana
function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function capitalize(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
}
}
