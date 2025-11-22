// FILE: Js/scanner_logic.js
// REVISI FINAL: Auto-Select Kendaraan dari QR & Manual Input

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

// 1. INITIALIZE
function initializeScanner() {
    try {
        if (typeof ZXing === 'undefined') {
            console.error('❌ ZXing library tidak ditemukan!');
            return false;
        }
        codeReader = new ZXing.BrowserMultiFormatReader();
        return true;
    } catch (error) {
        console.error('❌ Error init:', error);
        return false;
    }
}

// 2. UTILITIES
function setActionButtons(enableMasuk, enableKeluar) {
    btnMasuk.disabled = !enableMasuk;
    btnKeluar.disabled = !enableKeluar;
    
    // Style Visual
    btnMasuk.style.opacity = enableMasuk ? "1" : "0.5";
    btnMasuk.style.cursor = enableMasuk ? "pointer" : "not-allowed";
    
    btnKeluar.style.opacity = enableKeluar ? "1" : "0.5";
    btnKeluar.style.cursor = enableKeluar ? "pointer" : "not-allowed";
}

function resetVehicleDetails() {
    jenisKendaraanInput.value = '';
    warnaKendaraanInput.value = '';
}

function resetForm() {
    current_user_id = null;
    userCodeInput.value = '';
    platNomorSelect.innerHTML = '<option value="">-- Menunggu Input --</option>';
    platNomorSelect.disabled = true;
    resetVehicleDetails();
    setActionButtons(false, false);
}

function updateVehicleDetails() {
    const selectedOption = platNomorSelect.options[platNomorSelect.selectedIndex];
    if (selectedOption && selectedOption.value) {
        jenisKendaraanInput.value = selectedOption.getAttribute('data-jenis') || '-';
        warnaKendaraanInput.value = selectedOption.getAttribute('data-warna') || '-';
    } else {
        resetVehicleDetails();
    }
}

// =========================================================
// 3. CORE LOGIC: HANDLER HASIL SCAN / INPUT MANUAL
// =========================================================

function handleScanResult(inputValue) {
    const parts = inputValue.split(':');
    
    let userId = null;
    let targetPlat = null; // Plat yang dituju (dari QR)

    // KONDISI 1: Scan QR (Format: PARKIR_UMK : ID : PLAT)
    if (parts.length === 3 && parts[0] === 'PARKIR_UMK') {
        userId = parts[1];
        targetPlat = parts[2]; // Ambil plat dari QR
        
        console.log(`✅ QR Scan: User ${userId}, Plat ${targetPlat}`);
        userCodeInput.value = userId; // Tampilkan ID saja biar bersih
    } 
    // KONDISI 2: Input Manual (Hanya ID)
    else if (!inputValue.includes(':')) {
        userId = inputValue.trim();
        targetPlat = null; // Tidak ada target plat spesifik
        
        console.log(`⌨️ Manual Input: User ${userId}`);
    } 
    else {
        Swal.fire('Error', 'Format tidak dikenali. Gunakan QR Valid atau ketik ID.', 'error');
        return;
    }

    current_user_id = userId;
    
    // Matikan kamera jika sedang menyala
    if (isScanning) stopScanner();

    // Panggil fungsi fetch dengan Target Plat
    fetchUserVehicleData(userId, targetPlat);
}

// =========================================================
// 4. FETCH DATA & POPULATE (AUTO-SELECT LOGIC)
// =========================================================

function fetchUserVehicleData(userId, targetPlat = null) {
    platNomorSelect.innerHTML = '<option>Memuat...</option>';
    platNomorSelect.disabled = true;

    fetch(`scan_kendaraan.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
        if (data.success && data.vehicles.length > 0) {
            populateVehicleSelect(data.vehicles, data.active_plat_nomor, data.active_status, targetPlat);

            // [LOGIKA BARU] Auto-Open Map jika status user adalah 'keluar' (artinya dia mau MASUK)
            // Kita beri delay sedikit (500ms) biar animasi smooth setelah bunyi 'Beep' scan
            if (data.active_status !== 'masuk') {
                setTimeout(() => {
                    if (typeof openSlotSheet === 'function') {
                        openSlotSheet(); // Panggil fungsi buka map yang ada di scan_qrcode.php
                    }
                }, 500);
            }

        } else {
                platNomorSelect.innerHTML = '<option value="">Tidak ada kendaraan</option>';
                Swal.fire('Info', 'User ID ditemukan tapi tidak memiliki kendaraan.', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            platNomorSelect.innerHTML = '<option>Error Server</option>';
        });
}

function populateVehicleSelect(vehicles, activePlatDB, activeStatusDB, targetPlatQR) {
    platNomorSelect.innerHTML = '<option value="">-- Pilih Kendaraan --</option>';
    platNomorSelect.disabled = false;
    
    let isSelected = false;

    vehicles.forEach(v => {
        const option = document.createElement('option');
        option.value = v.plat_nomor;
        option.textContent = `${v.plat_nomor} (${v.jenis})`;
        option.setAttribute('data-jenis', v.jenis);
        option.setAttribute('data-warna', v.warna);

        // LOGIKA AUTO-SELECT (PENTING!)
        // 1. Jika ada target dari QR, pilih itu.
        if (targetPlatQR && v.plat_nomor === targetPlatQR) {
            option.selected = true;
            isSelected = true;
        }
        // 2. Jika tidak ada target QR (Manual), tapi status user sedang 'MASUK', pilih plat yang sedang parkir.
        else if (!targetPlatQR && activeStatusDB === 'masuk' && v.plat_nomor === activePlatDB) {
            option.selected = true;
            isSelected = true;
        }
        
        platNomorSelect.appendChild(option);
    });

    // 3. Fallback: Jika input manual & cuma punya 1 kendaraan, otomatis pilih.
    if (!isSelected && vehicles.length === 1) {
        platNomorSelect.selectedIndex = 1;
        isSelected = true;
    }

    // Trigger update tampilan info jika ada yang terpilih
    if (isSelected) {
        updateVehicleDetails();
    }

    // Atur tombol Masuk/Keluar berdasarkan status terakhir di DB
    if (activeStatusDB === 'masuk') {
        // User sedang di dalam -> Tombol Keluar Aktif
        setActionButtons(false, true);
    } else {
        // User di luar -> Tombol Masuk Aktif
        setActionButtons(true, false);
    }
}

// =========================================================
// 5. SCANNER & SUBMIT (SAMA SEPERTI SEBELUMNYA)
// =========================================================

async function startScanner() {
    try {
        if (!codeReader) initializeScanner();
        
        const devices = await codeReader.listVideoInputDevices();
        // Pilih kamera belakang jika ada
        const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang'));
        const deviceId = backCam ? backCam.deviceId : devices[0].deviceId;

        scannerContainer.style.display = 'flex';
        document.getElementById('cameraPlaceholder').style.display = 'none';
        
        toggleScannerBtn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Kamera';
        toggleScannerBtn.classList.add('active');
        isScanning = true;

        await codeReader.decodeFromVideoDevice(deviceId, videoElement, (res, err) => {
            if (res) {
                handleScanResult(res.text);
            }
        });
    } catch (err) {
        Swal.fire('Error Kamera', 'Pastikan izin kamera diberikan.', 'error');
    }
}

function stopScanner() {
    if(codeReader) codeReader.reset();
    scannerContainer.style.display = 'none';
    document.getElementById('cameraPlaceholder').style.display = 'flex';
    toggleScannerBtn.innerHTML = '<i class="fa-solid fa-camera"></i> Aktifkan Kamera';
    toggleScannerBtn.classList.remove('active');
    isScanning = false;
}

function submitParking(action) {
    const plat = platNomorSelect.value;
    
    // 1. Ambil nilai Slot dari hidden input (Hasil pilihan dari Map)
    const kodeArea = document.getElementById('kode_area_input').value;

    if (!current_user_id || !plat) {
        Swal.fire('Gagal', 'Pilih kendaraan terlebih dahulu.', 'warning');
        return;
    }

    // 2. VALIDASI KHUSUS MASUK: Wajib pilih slot
    if (action === 'masuk' && !kodeArea) {
        Swal.fire('Peringatan', 'Lokasi parkir wajib dipilih lewat peta!', 'warning');
        // Buka otomatis sheet-nya biar petugas ingat
        if (typeof openSlotSheet === 'function') {
            openSlotSheet();
        }
        return;
    }

    const formData = new FormData();
    formData.append('user_id', current_user_id);
    formData.append('plat_nomor', plat);
    formData.append('action', action);
    
    // 3. KIRIM KODE SLOT KE SERVER
    formData.append('kode_area', kodeArea);

    Swal.fire({ title: 'Memproses...', didOpen: () => Swal.showLoading() });

    fetch('process_parkir.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success');
                setTimeout(() => {
                    resetForm();
                    // Reset tampilan tombol pilihan slot juga
                    document.getElementById('kode_area_input').value = '';
                    document.getElementById('slotLabelText').innerText = '-- Pilih Lewat Peta --';
                    document.getElementById('btnTriggerSlot').classList.remove('filled');
                }, 1500);
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
}

// =========================================================
// 6. EVENT LISTENERS
// =========================================================

document.addEventListener('DOMContentLoaded', () => {
    initializeScanner();
    resetForm(); // Bersihkan saat load

    // Toggle Kamera
    toggleScannerBtn.addEventListener('click', () => {
        isScanning ? stopScanner() : startScanner();
    });

    // Input Manual (Detect Enter / Blur)
    userCodeInput.addEventListener('change', (e) => {
        const val = e.target.value.trim();
        if (val) handleScanResult(val);
    });

    // Dropdown Change
    platNomorSelect.addEventListener('change', updateVehicleDetails);

    // Tombol Aksi
    btnMasuk.addEventListener('click', () => submitParking('masuk'));
    btnKeluar.addEventListener('click', () => submitParking('keluar'));
});