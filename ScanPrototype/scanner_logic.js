// FILE: Js/scanner_logic.js (REVISI FINAL - Konsistensi Kamera Belakang)

// --- DECLARATIONS ---
let codeReader = null;
const videoElement = document.getElementById('scanner-video');
const scannerContainer = document.getElementById('scanner-container');
const toggleScannerBtn = document.getElementById('toggleScannerBtn');
const userCodeInput = document.getElementById('user_code');
const platNomorSelect = document.getElementById('plat_nomor');
const jenisKendaraanInput = document.getElementById('jenis_kendaraan');
const warnaKendaraanInput = document.getElementById('warna_kendaraan');
const btnMasuk = document.getElementById('btnMasuk');
const btnKeluar = document.getElementById('btnKeluar');

let current_user_id = null; 
let vehicleData = []; 
let isScanning = false;

// =========================================================
// INITIALIZE ZXING (PENTING!)
// =========================================================

function initializeScanner() {
    try {
        if (typeof ZXing === 'undefined') {
            console.error('❌ ZXing library tidak ditemukan!');
            return false;
        }
        
        codeReader = new ZXing.BrowserMultiFormatReader();
        console.log('✅ ZXing Scanner initialized successfully');
        return true;
    } catch (error) {
        console.error('❌ Error initializing scanner:', error);
        return false;
    }
}

// =========================================================
// UTILITY & RESET FUNCTIONS
// =========================================================

function setActionButtons(enableMasuk, enableKeluar) {
    btnMasuk.disabled = !enableMasuk;
    btnKeluar.disabled = !enableKeluar;
}

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
    // Default: Nonaktifkan semua sampai data terisi setelah scan
    setActionButtons(false, false); 
}

function updateVehicleDetails() {
    const selectedOption = platNomorSelect.options[platNomorSelect.selectedIndex];
    if (selectedOption && selectedOption.value) {
        jenisKendaraanInput.value = selectedOption.getAttribute('data-jenis') || '';
        warnaKendaraanInput.value = selectedOption.getAttribute('data-warna') || '';
    } else {
        resetVehicleDetails();
    }
}

// =========================================================
// FETCH DATA KENDARAAN (AJAX)
// =========================================================

function fetchUserVehicleData(user_id) {
    fetch(`scan_kendaraan.php?user_id=${user_id}`) 
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil data kendaraan. (HTTP Error)');
            return response.json();
        })
        .then(data => {
            if (!data.vehicles || data.vehicles.length === 0) {
                 Swal.fire('Informasi', 'Tidak ada data kendaraan terdaftar.', 'info');
                 resetForm();
                 return;
            }
            populateVehicleSelect(data.vehicles, data.active_plat_nomor, data.active_status);
        })
        .catch(error => {
            console.error('Fetch error:', error);
            Swal.fire('Error', 'Gagal memuat data: ' + error.message, 'error');
            resetForm();
        });
}

function populateVehicleSelect(vehicles, active_plat_nomor, active_status) {
    platNomorSelect.innerHTML = '<option value="">-- Pilih Kendaraan --</option>';
    let defaultSelected = false;
    vehicleData = vehicles; 
    let platNomorAktif = active_plat_nomor;
    
    // 1. Isi Pilihan Kendaraan
    if (vehicles && vehicles.length > 0) {
        platNomorSelect.disabled = false;
        
        vehicles.forEach(v => {
            const option = document.createElement('option');
            option.value = v.plat_nomor;
            option.textContent = v.plat_nomor;
            option.setAttribute('data-jenis', v.jenis || ''); 
            option.setAttribute('data-warna', v.warna || '');

            // Auto-select logic
            if ((active_plat_nomor && v.plat_nomor === active_plat_nomor) || (vehicles.length === 1 && active_status === 'keluar')) {
                option.selected = true;
                defaultSelected = true;
                platNomorAktif = v.plat_nomor;
            }
            
            platNomorSelect.appendChild(option);
        });
    }

    if (defaultSelected) {
        updateVehicleDetails(); 
    } else {
        resetVehicleDetails();
    }
    
    // 2. Tentukan Status Tombol Aksi
    if (active_status === 'masuk') {
        // User sedang parkir -> Siap Catat KELUAR
        platNomorSelect.disabled = true; 
        setActionButtons(false, true); // Nonaktif Masuk, Aktif Keluar
        
    } else {
        // User sedang keluar -> Siap Catat MASUK
        if (vehicles.length > 0) {
            platNomorSelect.disabled = false;
            setActionButtons(true, false); // Aktif Masuk, Nonaktif Keluar
        } else {
            // Tidak ada kendaraan terdaftar
            platNomorSelect.disabled = true;
            setActionButtons(false, false);
        }
    }
}

// =========================================================
// LOGIKA PEMROSESAN HASIL SCAN
// =========================================================

function handleScanResult(qr_data) {
    const parts = qr_data.split(':');
    if (parts.length !== 2 || parts[0] !== 'PARKIR_UMK') {
        Swal.fire('Error', 'Format QR Code tidak valid. Expected: PARKIR_UMK:USER_ID', 'error');
        resetForm();
        return;
    }

    current_user_id = parts[1];
    userCodeInput.value = qr_data;

    console.log('✅ QR Code valid, User ID:', current_user_id);
    fetchUserVehicleData(current_user_id);
}

// =========================================================
// SCANNER CONTROL (REVISI PEMILIHAN KAMERA)
// =========================================================

async function startScanner() {
    try {
        if (!codeReader) {
            const initialized = initializeScanner();
            if (!initialized) return;
        }
        
        console.log('📷 Requesting camera access...');
        
        toggleScannerBtn.textContent = '⏳ Membuka Kamera...';
        toggleScannerBtn.disabled = true;
        
        const videoInputDevices = await codeReader.listVideoInputDevices();
        
        if (videoInputDevices.length === 0) {
            throw new Error('Tidak ada kamera yang terdeteksi. Pastikan kamera terhubung dan izin diberikan.');
        }
        
        // **LOGIKA REVISI: Memilih Kamera Belakang secara eksplisit**
        let selectedDeviceId = videoInputDevices[0].deviceId; // Default
        
        // Cari perangkat dengan label yang mengindikasikan kamera belakang/environment
        const backCamera = videoInputDevices.find(device => 
            /back|environment|belakang/i.test(device.label)
        );
        
        if (backCamera) {
            selectedDeviceId = backCamera.deviceId;
            console.log(`✅ Memilih kamera: ${backCamera.label}`);
        } else if (videoInputDevices.length > 1) {
             // Jika ada lebih dari satu kamera, coba perangkat terakhir (seringkali kamera belakang)
             selectedDeviceId = videoInputDevices[videoInputDevices.length - 1].deviceId;
             console.log(`⚠️ Kamera belakang tidak terdeteksi labelnya, menggunakan perangkat terakhir.`);
        } else {
             console.log(`⚠️ Hanya satu kamera ditemukan, menggunakan default.`);
        }
        
        scannerContainer.style.display = 'block';
        isScanning = true;
        
        // Memulai decoding dengan Device ID yang dipilih
        await codeReader.decodeFromVideoDevice(selectedDeviceId, videoElement, (result, error) => {
            if (result) {
                console.log('✅ Scan result:', result.text);
                handleScanResult(result.text);
                stopScanner(); 
            }
            
            if (error && error.name !== 'NotFoundException') {
                console.warn('Scan error:', error);
            }
        });
        
        toggleScannerBtn.textContent = '⏹️ Stop Scan';
        toggleScannerBtn.disabled = false;
        toggleScannerBtn.style.background = '#E74C3C';
        
    } catch (error) {
        console.error('❌ Camera error:', error);
        
        let errorMessage = 'Gagal mengakses kamera.';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            errorMessage = 'Izin kamera ditolak. Silakan izinkan akses kamera di pengaturan browser.';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            errorMessage = 'Kamera tidak ditemukan. Pastikan kamera terhubung.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            errorMessage = 'Kamera sedang digunakan aplikasi lain.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Kamera Error',
            text: errorMessage,
            footer: 'Tips: Pastikan akses via localhost dan izinkan permission kamera'
        });
        
        stopScanner();
    }
}

function stopScanner() {
    if (codeReader) {
        codeReader.reset();
        console.log('🛑 Scanner stopped');
    }
    
    scannerContainer.style.display = 'none';
    isScanning = false;
    
    toggleScannerBtn.textContent = '📷 Scan Kamera';
    toggleScannerBtn.disabled = false;
    toggleScannerBtn.style.background = '';
}

function toggleScanner() {
    if (isScanning) {
        stopScanner();
    } else {
        startScanner();
    }
}

// =========================================================
// SUBMIT PARKING (AJAX)
// =========================================================

function submitParking(action) {
    if (!current_user_id || !platNomorSelect.value) {
        Swal.fire('Peringatan', 'Harap Scan/Input Kode Pengguna dan pilih Plat Nomor dahulu.', 'warning');
        return;
    }

    const plat_nomor = platNomorSelect.value;
    
    const formData = new FormData();
    formData.append('user_id', current_user_id);
    formData.append('plat_nomor', plat_nomor);
    formData.append('action', action);
    
    // DISABLE TOMBOL SAAT PROSES
    setActionButtons(false, false); 

    // Show loading
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
        Swal.close(); 
        
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: `Catat ${action.toUpperCase()} Berhasil!`,
                html: data.message, 
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            
            // RESET FORM setelah berhasil untuk mempersiapkan transaksi baru
            setTimeout(() => { resetForm(); }, 1000); 
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                html: data.message || 'Terjadi kesalahan saat memproses data.'
            });
            // Jika gagal, coba ambil ulang data pengguna untuk mengatur ulang tombol
            if (current_user_id) fetchUserVehicleData(current_user_id);
            else resetForm();
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Submit error:', error);
        Swal.fire('Error Fatal', `Terjadi kesalahan: ${error.message}`, 'error');
        // Jika error fatal, reset form
        resetForm();
    });
}

// =========================================================
// EVENT HANDLERS INITIALIZATION
// =========================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Scanner Logic Initialized');
    
    // Initialize scanner on load
    initializeScanner();
    
    // Toggle scanner button
    toggleScannerBtn.addEventListener('click', toggleScanner);
    
    // Manual input handler
    userCodeInput.addEventListener('change', () => {
        if (userCodeInput.value.trim()) {
            // Stop scanner jika sedang berjalan (agar video element dilepaskan)
            if (isScanning) stopScanner();
            handleScanResult(userCodeInput.value.trim());
        }
    });
    
    // Parking action buttons
    btnMasuk.addEventListener('click', () => {
        submitParking('masuk');
    });
    
    btnKeluar.addEventListener('click', () => {
        submitParking('keluar');
    });

    // Vehicle select change handler
    platNomorSelect.addEventListener('change', updateVehicleDetails);
    
    // Initial form reset
    resetForm();
    
    console.log('✅ All event handlers attached');
});