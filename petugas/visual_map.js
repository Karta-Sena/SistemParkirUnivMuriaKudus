/**
 * Visual Map Logic - Mobile Friendly Fix
 */

function showSlotDetail(element) {
    const modal = document.getElementById('detailModal');
    // Ambil dataset dengan aman
    const data = element.dataset;

    // Elements
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');
    const statusBadge = document.getElementById('modalStatus');
    const icon = document.getElementById('modalIcon');
    const iconBg = document.getElementById('modalIconBg');
    const occupiedInfo = document.getElementById('occupiedInfo');

    // 1. Set Judul (Cegah "Slot -")
    // Jika data.slot kosong, gunakan fallback
    title.innerText = "Slot " + (data.slot ? data.slot : "??");
    
    // 2. Reset Tampilan Awal
    statusBadge.className = 'status-badge';
    iconBg.className = 'detail-icon-circle';

    // 3. Logika Status
    if (data.status === 'occupied') {
        // -- KONDISI TERISI --
        statusBadge.textContent = "Terisi";
        statusBadge.style.background = "#fee2e2"; // Merah muda (Hardcode agar aman)
        statusBadge.style.color = "#ef4444";     // Merah
        
        icon.className = "fa-solid fa-car";
        iconBg.classList.add('occupied'); // Class CSS untuk bg merah
        
        // Tampilkan Detail
        occupiedInfo.style.display = 'block';
        
        // Isi Data (Gunakan 'Tamu' jika kosong)
        document.getElementById('modalOwner').textContent = data.owner || 'Tamu / Umum';
        document.getElementById('modalPlat').textContent = data.plat || '-';
        
        // --- FIX DATE PARSING UNTUK MOBILE ---
        // Format data.time dari PHP biasanya "2025-11-22 17:00:00"
        // iPhone/Safari butuh format "2025/11/22 17:00:00" atau ISO
        let timeString = data.time;
        if(timeString) {
            // Tampilkan Jam Saja
            let timeParts = timeString.split(' '); // Pisahkan tanggal dan jam
            let jamMenit = timeParts.length > 1 ? timeParts[1].substring(0, 5) : timeString;
            document.getElementById('modalTime').textContent = jamMenit + " WIB";

            // Hitung Durasi
            // Ganti "-" jadi "/" agar terbaca di Safari/iOS
            let safeDateStr = timeString.replace(/-/g, "/"); 
            let entryTime = new Date(safeDateStr);
            let now = new Date();

            if (!isNaN(entryTime)) {
                let diffMs = now - entryTime;
                let diffHrs = Math.floor(diffMs / 3600000);
                let diffMins = Math.round(((diffMs % 3600000) / 60000));
                document.getElementById('modalDuration').textContent = `${diffHrs} jam ${diffMins} menit`;
            } else {
                document.getElementById('modalDuration').textContent = "Baru saja";
            }
        } else {
             document.getElementById('modalTime').textContent = "-";
             document.getElementById('modalDuration').textContent = "-";
        }

    } else if (data.status === 'available') {
        // -- KONDISI TERSEDIA --
        statusBadge.textContent = "Tersedia";
        statusBadge.style.background = "#d1fae5"; // Hijau muda
        statusBadge.style.color = "#10b981";     // Hijau
        
        icon.className = "fa-solid fa-check";
        iconBg.classList.add('available');
        
        occupiedInfo.style.display = 'none';

    } else {
        // -- KONDISI RUSAK --
        statusBadge.textContent = "Rusak / Maintenance";
        statusBadge.style.background = "#f1f5f9";
        statusBadge.style.color = "#64748b";
        icon.className = "fa-solid fa-triangle-exclamation";
        occupiedInfo.style.display = 'none';
    }

    // 4. Tampilkan Modal (Menggunakan Flex)
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('detailModal');
    if (event.target == modal) {
        closeModal();
    }
}