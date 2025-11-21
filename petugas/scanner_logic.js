// FILE: Js/scanner_logic.js (FINAL ULTIMATE - TANPA KONFIRMASI)

// --- DECLARATIONS ---
const codeReader = new ZXing.BrowserMultiFormatReader();
const videoElement = document.getElementById('scanner-video');
const scannerContainer = document.getElementById('scanner-container');
const toggleScannerBtn = document.getElementById('toggleScannerBtn');
const userCodeInput = document.getElementById('user_code');
const platNomorSelect = document.getElementById('plat_nomor');
const jenisKendaraanInput = document.getElementById('jenis_kendaraan');
const warnaKendaraanInput = document.getElementById('warna_kendaraan');

let current_user_id = null; 
let vehicleData = []; 

// =========================================================
// UTILITY & RESET FUNCTIONS (TETAP SAMA)
// =========================================================

function resetVehicleDetails() {
    jenisKendaraanInput.value = '';
    warnaKendaraanInput.value = '';
}

function resetForm() {
    current_user_id = null;
    userCodeInput.value = '';
    platNomorSelect.innerHTML = '<option value="">-- Scan/Input Kode Pengguna Dahulu --</option>';
    platNomorSelect.disabled = true;
    resetVehicleDetails();
    vehicleData = []; 
}

function updateVehicleDetails() {
    const selectedOption = platNomorSelect.options[platNomorSelect.selectedIndex];
    if (selectedOption && selectedOption.value) {
        jenisKendaraanInput.value = selectedOption.getAttribute('data-jenis');
        warnaKendaraanInput.value = selectedOption.getAttribute('data-warna');
    } else {
        resetVehicleDetails();
    }
}

// =========================================================
// 4. FETCH DATA KENDARAAN (AJAX) (TETAP SAMA)
// =========================================================

function fetchUserVehicleData(user_id) {
    fetch(`scan_kendaraan.php?user_id=${user_id}`) 
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil data kendaraan.');
            return response.json();
        })
        .then(data => {
            if (!data.vehicles) {
                 Swal.fire('Informasi', 'Tidak ada data kendaraan terdaftar.', 'info');
                 resetForm();
                 return;
            }
            populateVehicleSelect(data.vehicles, data.active_plat_nomor, data.active_status);
        })
        .catch(error => {
            Swal.fire('Error', 'Gagal memuat data: ' + error.message, 'error');
            resetForm();
        });
}

function populateVehicleSelect(vehicles, active_plat_nomor, active_status) {
    platNomorSelect.innerHTML = '<option value="">-- Pilih Kendaraan --</option>';
    let defaultSelected = false;
    vehicleData = vehicles; 

    if (vehicles && vehicles.length > 0) {
        platNomorSelect.disabled = false;
        
        vehicles.forEach(v => {
            const option = document.createElement('option');
            option.value = v.plat_nomor;
            option.textContent = v.plat_nomor;
            option.setAttribute('data-jenis', v.jenis || ''); 
            option.setAttribute('data-warna', v.warna || '');

            // LOGIKA PENTING: Pilih Plat Nomor Otomatis
            if ( (vehicles.length === 1) || (active_plat_nomor && v.plat_nomor === active_plat_nomor) ) {
                option.selected = true;
                defaultSelected = true;
            }
            
            platNomorSelect.appendChild(option);
        });
    }

    if (defaultSelected) {
        updateVehicleDetails(); 
    } else {
        resetVehicleDetails();
    }
    
    // Nonaktifkan SELECT jika status MASUK 
    platNomorSelect.disabled = (active_status === 'masuk');
}


// =========================================================
// 3. LOGIKA PEMROSESAN HASIL SCAN (TETAP SAMA)
// =========================================================

function handleScanResult(qr_data) {
    const parts = qr_data.split(':');
    if (parts.length !== 2 || parts[0] !== 'PARKIR_UMK') {
        Swal.fire('Error', 'Format QR Code tidak valid.', 'error');
        resetForm();
        return;
    }

    current_user_id = parts[1];
    userCodeInput.value = qr_data;

    fetchUserVehicleData(current_user_id);
}

// =========================================================
// 2. LOGIKA SCANNER (TETAP SAMA)
// =========================================================

function startScanner() {
    codeReader.decodeFromVideoDevice(null, videoElement, (result, err) => {
        if (result) {
            handleScanResult(result.text);
            toggleScanner(); 
        }
    });
}
function stopScanner() { codeReader.reset(); }
function toggleScanner() {
    if (scannerContainer.style.display === 'none') {
        scannerContainer.style.display = 'block';
        toggleScannerBtn.textContent = 'Tutup Scanner ❌';
        codeReader.listVideoInputDevices().then(() => startScanner()).catch(() => {
            Swal.fire('Error Kamera', 'Gagal mengakses kamera.', 'error');
            scannerContainer.style.display = 'none';
        }); 
    } else {
        scannerContainer.style.display = 'none';
        toggleScannerBtn.textContent = 'Scan Kamera 📷';
        stopScanner();
    }
}


// =========================================================
// 5. SUBMIT PARKING (AJAX) - KUNCI PERUBAHAN
// =========================================================

function submitParking(action) {
    if (!current_user_id || !platNomorSelect.value) {
        Swal.fire('Peringatan', 'Harap Scan/Input Kode Pengguna dan pilih Plat Nomor dahulu.', 'warning');
        return;
    }

    const plat_nomor = platNomorSelect.value;
    
    // **HAPUS KONFIRMASI SweetAlert**
    // Lanjut langsung ke proses AJAX

    const formData = new FormData();
    formData.append('user_id', current_user_id);
    formData.append('plat_nomor', plat_nomor);
    formData.append('action', action);

    // Tampilkan notifikasi loading sementara
    Swal.fire({
        title: 'Memproses...',
        text: `Mencatat ${action.toUpperCase()} Plat ${plat_nomor}...`,
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('process_parkir.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Kesalahan Jaringan/Server');
        return response.json(); 
    })
    .then(data => {
        // Hapus loading
        Swal.close(); 
        
        if (data.status === 'success') {
            // Tampilkan notifikasi sukses di atas
            Swal.fire({
                icon: 'success',
                title: `Catat ${action.toUpperCase()} Berhasil!`,
                html: data.message, 
                toast: true, // Notifikasi kecil di sudut layar
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            
            // Reset form setelah sukses
            setTimeout(() => { resetForm(); }, 1000); 
            
        } else {
            // Tampilkan pesan kegagalan/error
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                html: data.message || 'Terjadi kesalahan saat memproses data.'
            });
        }
    })
    .catch(error => {
        Swal.close(); // Hapus loading jika ada error jaringan
        Swal.fire('Error Fatal', `Terjadi kesalahan: ${error.message}`, 'error');
    });
}


// =========================================================
// 1. INJEKSI EVENT HANDLERS (TETAP SAMA)
// =========================================================

document.addEventListener('DOMContentLoaded', () => {
    toggleScannerBtn.addEventListener('click', toggleScanner);
    
    userCodeInput.addEventListener('change', () => {
        handleScanResult(userCodeInput.value);
    });
    
    document.getElementById('btnMasuk').addEventListener('click', () => {
        submitParking('masuk');
    });
    document.getElementById('btnKeluar').addEventListener('click', () => {
        submitParking('keluar');
    });

    platNomorSelect.addEventListener('change', updateVehicleDetails);
    
    resetForm();
});