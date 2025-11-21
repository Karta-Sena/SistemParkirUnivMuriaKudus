document.addEventListener("DOMContentLoaded", function() {
    
    // --- SETUP ---
    const btnGenerate = document.getElementById('actionBtn');
    const flipCard = document.getElementById('flipCard');
    const qrFront = document.getElementById('qr-code-front');
    const qrBack = document.getElementById('qr-code-back');
    const placeholderFront = document.getElementById('placeholder-front');
    const swingEl = document.getElementById('swingElement');
    const noVehicleErrorDiv = document.getElementById('no-vehicle-error'); 
    
    // --- DATA ---
    const userId = String(window.userId || '0').trim();
    const initialStatus = (window.initialStatus || 'keluar').trim().toLowerCase();
    const currentPlat = String(window.platNomor || '').trim();

    let isQrGenerated = false;
    let isCardFlipped = false;
    
    // Validasi
    const hasValidVehicle = currentPlat !== '' && currentPlat !== '-----' && currentPlat !== 'null';
    
    // =====================================================
    // FUNGSI RENDER (SOLUSI INTI)
    // =====================================================
    function renderQrToElements() {
        if (!hasValidVehicle) return false;
        
        // 1. URL QR (Format Sesuai Overview)
        const qrString = `PARKIR_UMK:${userId}:${currentPlat}`;
        const qrUrl = `generate_qr.php?text=${encodeURIComponent(qrString)}&t=${Date.now()}`;
        
        // 2. HTML Tag (Paksa ukuran di tag juga)
        const imgHtml = `<img src="${qrUrl}" alt="QR Parkir" width="160" height="160" style="width:160px; height:160px; border-radius:12px;">`;

        // 3. NUCLEAR FIX: Paksa Display Flex menggunakan cssText
        // Ini akan menimpa inline style 'display: none' di HTML
        if (qrFront) {
            qrFront.innerHTML = imgHtml;
            qrFront.style.cssText = 'display: flex !important; justify-content: center; align-items: center; width: 100%; height: 100%; opacity: 1;';
        }
        
        if (qrBack) {
            qrBack.innerHTML = imgHtml;
            qrBack.style.cssText = 'display: flex !important; justify-content: center; align-items: center; width: 100%; height: 100%; opacity: 1;';
        }
        
        // 4. HILANGKAN PLACEHOLDER & ERROR
        if (placeholderFront) placeholderFront.style.setProperty('display', 'none', 'important');
        if (noVehicleErrorDiv) noVehicleErrorDiv.style.setProperty('display', 'none', 'important');
        
        return true;
    }

    // =====================================================
    // LOGIKA GENERATE
    // =====================================================
    function generateQR() {
        if (!hasValidVehicle) return;

        renderQrToElements();

        // Update Tombol Visual
        if (btnGenerate) {
            btnGenerate.className = 'action-pill pill-masuk';
            btnGenerate.innerHTML = '<i class="fa-solid fa-expand"></i> SIAP SCAN';
            btnGenerate.style.cursor = 'default';
        }
        
        isQrGenerated = true;
        sessionStorage.setItem('qr_active_session', 'true');
    }

    function resetToGenerateMode() {
        // Sembunyikan QR
        if (qrFront) { qrFront.innerHTML = ""; qrFront.style.display = 'none'; }
        if (qrBack) { qrBack.innerHTML = ""; qrBack.style.display = 'none'; }
        
        // Tampilkan Placeholder
        if (hasValidVehicle && placeholderFront) {
            placeholderFront.style.display = 'block';
            if (btnGenerate) {
                btnGenerate.className = 'action-pill pill-generate';
                btnGenerate.innerHTML = '<i class="fa-solid fa-qrcode"></i> GENERATE';
                btnGenerate.style.cursor = 'pointer';
            }
        }
        
        isQrGenerated = false;
        isCardFlipped = false;
        sessionStorage.removeItem('qr_active_session');
    }

    function flipToBack() {
        if (!hasValidVehicle) return;
        if (!isQrGenerated) generateQR();
        gsap.to(flipCard, { duration: 0.8, rotationY: 180, ease: "back.out(1.5)", overwrite: true });
        isCardFlipped = true;
    }

    function flipToFront() {
        gsap.to(flipCard, { duration: 0.8, rotationY: 0, ease: "back.out(1.5)", overwrite: true });
        isCardFlipped = false;
    }

    // =====================================================
    // INIT
    // =====================================================
    if (hasValidVehicle) {
        const isSessionActive = sessionStorage.getItem('qr_active_session') === 'true';

        if (initialStatus === 'masuk') {
            generateQR();
            gsap.set(flipCard, { rotationY: 180 });
            isCardFlipped = true;
        } 
        else if (isSessionActive) {
            generateQR();
            gsap.set(flipCard, { rotationY: 0 });
        } 
        else {
            resetToGenerateMode();
            gsap.set(flipCard, { rotationY: 0 });
        }
    }

    // =====================================================
    // EVENT LISTENERS
    // =====================================================
    if (btnGenerate) {
        btnGenerate.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!isQrGenerated) generateQR();
        });
    }
    
    // Lanyard Physics
    function initPhysics() {
        if (!swingEl) return;

        // 1. Fungsi Kalkulasi Gerakan (Re-usable)
        const animateSwing = (clientX, clientY) => {
            const w = window.innerWidth;
            const h = window.innerHeight;
            
            // Menghitung posisi relatif (-0.5 s/d 0.5)
            const xPos = (clientX / w) - 0.5;
            const yPos = (clientY / h) - 0.5;

            gsap.to(swingEl, { 
                duration: 1.5, 
                rotationY: xPos * 50,   // Miring Kiri-Kanan (Lebih responsif)
                rotationX: -yPos * 40,  // Miring Depan-Belakang
                rotationZ: xPos * 20,   // Miring Z axis
                ease: "power2.out", 
                transformPerspective: 1000, 
                transformOrigin: "top center" 
            });
        };

        // 2. Fungsi Reset (Kembali Diam)
        const resetSwing = () => {
            gsap.to(swingEl, { 
                duration: 2.5, 
                rotationY: 0, 
                rotationX: 0, 
                rotationZ: 0, 
                ease: "elastic.out(1, 0.3)" // Efek memantul saat dilepas
            });
        };

        // --- EVENT LISTENERS ---

        // A. DESKTOP (Mouse Move)
        document.addEventListener('mousemove', (e) => { 
            if (window.matchMedia("(hover: hover)").matches) {
                animateSwing(e.clientX, e.clientY);
            }
        });
        document.addEventListener('mouseleave', resetSwing);

        // B. MOBILE (Touch Move)
        document.addEventListener('touchmove', (e) => { 
            // Ambil koordinat jari pertama
            const touch = e.touches[0]; 
            animateSwing(touch.clientX, touch.clientY);
        }, { passive: true }); // passive: true agar scroll halaman tetap jalan lancar

        document.addEventListener('touchend', resetSwing);
    }
    
    initPhysics();

    // Polling
    setInterval(() => {
        if (userId === '0' || !hasValidVehicle) return;
        fetch(`check_status.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const dbStatus = (data.status_parkir || '').toLowerCase();
                
                if (dbStatus === 'masuk') {
                    if (!isQrGenerated) generateQR();
                    if (!isCardFlipped) flipToBack();
                } else if (dbStatus === 'keluar') {
                    if (isCardFlipped) {
                        flipToFront();
                        setTimeout(resetToGenerateMode, 800);
                    }
                }
            }).catch(console.error);
    }, 3000);
});