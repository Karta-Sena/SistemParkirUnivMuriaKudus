// ============================================================
// FILE: Js/qrcode_logic.js
// VERSI: Final + Validasi Kendaraan (Anti-Generate Tanpa Plat)
// ============================================================

document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. SETUP ELEMEN ---
    const btnGenerate = document.getElementById('actionBtn');
    const flipCard = document.getElementById('flipCard');
    const qrFront = document.getElementById('qr-code-front');
    const qrBack = document.getElementById('qr-code-back');
    const placeholderFront = document.getElementById('placeholder-front');
    const scannerBox = document.querySelector('.scanner-box');
    const swingEl = document.getElementById('swingElement');
    
    // Elemen Error Baru (Harus dicari di DOM)
    const noVehicleErrorDiv = document.getElementById('no-vehicle-error'); 
    
    // Ambil Data dari PHP
    const userId = window.userId || '0';
    const initialStatus = window.initialStatus || 'keluar';
    const currentPlat = window.platNomor || '-----'; 
    const QR_SIZE = 160;

    let isQrGenerated = false;
    let isCardFlipped = false;

    // --- FUNGSI-FUNGSI (Didefinisikan di awal agar bisa dipanggil di mana saja) ---

    // [INIT] Fungsi Fisika Didefinisikan Dulu
    function initPhysics() {
        if (swingEl) {
            function animateSwing(x, y, w, h) {
                const xPos = (x / w) - 0.5; const yPos = (y / h) - 0.5;
                gsap.to(swingEl, { duration: 1.5, rotationY: xPos * 40, rotationX: -yPos * 30, rotationZ: xPos * 15, ease: "power2.out", transformPerspective: 1000, transformOrigin: "top center" });
            }
            document.addEventListener('mousemove', (e) => { if (window.matchMedia("(hover: hover)").matches) animateSwing(e.clientX, e.clientY, window.innerWidth, window.innerHeight); });
            document.addEventListener('mouseleave', () => { gsap.to(swingEl, { duration: 2, rotationY: 0, rotationX: 0, rotationZ: 0, ease: "elastic.out(1, 0.3)" }); });
            document.addEventListener('touchmove', (e) => { const t = e.touches[0]; animateSwing(t.clientX, t.clientY, window.innerWidth, window.innerHeight); }, { passive: true });
            document.addEventListener('touchend', () => { gsap.to(swingEl, { duration: 2, rotationY: 0, rotationX: 0, rotationZ: 0, ease: "elastic.out(1, 0.3)" }); });
        }
    }
    initPhysics(); // Jalankan Fisika segera

    function generateQR() {
        const qrString = `PARKIR_UMK:${userId}`;
        
        if(qrFront) { qrFront.innerHTML = ""; new QRCode(qrFront, { text: qrString, width: QR_SIZE, height: QR_SIZE, colorDark : "#111827", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H }); }
        if(qrBack)  { qrBack.innerHTML = "";  new QRCode(qrBack,  { text: qrString, width: QR_SIZE, height: QR_SIZE, colorDark : "#111827", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H }); }
        
        if(placeholderFront) placeholderFront.style.display = 'none'; 
        if(noVehicleErrorDiv) noVehicleErrorDiv.style.display = 'none'; // Sembunyikan Error
        
        if(btnGenerate) {
            btnGenerate.className = 'action-pill pill-masuk'; 
            btnGenerate.innerHTML = '<i class="fa-solid fa-expand"></i> SCAN MASUK';
            btnGenerate.style.cursor = 'default';
        }
        
        isQrGenerated = true;
        sessionStorage.setItem('qr_active_session', 'true');
    }

    function resetToGenerateMode() {
        if(qrFront) qrFront.innerHTML = ""; 
        if(qrBack)  qrBack.innerHTML = "";
        
        if(placeholderFront) placeholderFront.style.display = 'block'; 
        if(noVehicleErrorDiv) noVehicleErrorDiv.style.display = 'none'; // Sembunyikan Error

        if(btnGenerate) {
            btnGenerate.className = 'action-pill pill-generate'; 
            btnGenerate.innerHTML = '<i class="fa-solid fa-qrcode"></i> GENERATE';
            btnGenerate.style.cursor = 'pointer';
            btnGenerate.style.background = ''; // Reset background jika ada style inline
            btnGenerate.style.boxShadow = '';
        }
        
        isQrGenerated = false;
        isCardFlipped = false;
        sessionStorage.removeItem('qr_active_session');
    }

    function flipToBack() {
        if (isCardFlipped) return;
        gsap.to(flipCard, { duration: 0.8, rotationY: 180, ease: "back.out(1.5)", overwrite: true });
        isCardFlipped = true;
    }

    function flipToFront() {
        if (!isCardFlipped) return;
        gsap.to(flipCard, { duration: 0.8, rotationY: 0, ease: "back.out(1.5)", overwrite: true });
    }

    // --- 3. VALIDASI KENDARAAN SEBELUM INIT ---
    if (currentPlat === '-----' || currentPlat === '') {
    const noVehicleErrorDiv = document.getElementById('no-vehicle-error'); 
    
    console.log("⛔ Tidak ada kendaraan terdeteksi. Mematikan fitur QR.");
    
    resetToGenerateMode(); // Pastikan bersih (Hapus QR lama)
    
    // 1. [FIX] INJECT KONTEN ERROR VISUAL LANGSUNG
    if (noVehicleErrorDiv) {
        // Konten sama persis dengan overview (Ikon & Teks Jelas)
        noVehicleErrorDiv.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #f97316; margin-bottom: 15px;"></i>
                                       <p style="font-weight: 700; color: #cc6300; font-size: 1rem; margin-bottom: 5px;">DATA KENDARAAN KOSONG</p>
                                       <p style="font-size: 0.75rem; color: #64748b;">Silakan pilih atau tambahkan kendaraan.</p>`;
        noVehicleErrorDiv.style.display = 'flex'; // Tampilkan Error Visual
    }
    if(placeholderFront) placeholderFront.style.display = 'none'; // Sembunyikan TAP TO GENERATE
    
    // 2. MATIKAN TOMBOL (Style Seragam Gray)
    if(btnGenerate) {
        btnGenerate.innerHTML = 'NO VEHICLE'; 
        btnGenerate.classList.remove('pill-generate', 'pill-masuk'); 
        btnGenerate.style.background = '#94a3b8'; 
        btnGenerate.style.backgroundColor = '#94a3b8';
        btnGenerate.style.cursor = 'not-allowed';
        btnGenerate.style.boxShadow = 'none';
        btnGenerate.style.pointerEvents = 'none';
    }
    
    initPhysics(); 
    return; // Hentikan script
    }

    // --- 4. LOGIKA INIT (Hanya jalan jika Plat Nomor Ada) ---
    const isSessionActive = sessionStorage.getItem('qr_active_session') === 'true';

    if (initialStatus === 'masuk') {
        generateQR();
        gsap.set(flipCard, { rotationY: 180 });
        isCardFlipped = true;
    } 
    else if (isSessionActive) {
        generateQR();
        gsap.set(flipCard, { rotationY: 0 });
        isCardFlipped = false;
    } 
    else {
        gsap.set(flipCard, { rotationY: 0 });
        isCardFlipped = false;
    }

    // --- 5. EVENT LISTENER ---
    if(btnGenerate) {
        btnGenerate.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            generateQR(); 
        });
    }
    if(scannerBox) {
        scannerBox.addEventListener('click', () => { 
            if (!isQrGenerated) generateQR(); 
        });
    }

    // --- 6. POLLING ---
    setInterval(() => {
        if (userId == '0') return;
        
        fetch(`check_status.php?user_id=${userId}`)
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  const dbStatus = data.status_parkir;
                  
                  if (dbStatus === 'masuk') { 
                      if (!isQrGenerated) generateQR(); 
                      if (!isCardFlipped) flipToBack(); 
                  }
                  else { 
                      if (isCardFlipped) { 
                          flipToFront();
                          setTimeout(resetToGenerateMode, 800); 
                      }
                  }
              }
          })
          .catch(err => console.error(err));
    }, 3000); 
    
});