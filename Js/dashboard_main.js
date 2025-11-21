document.addEventListener('DOMContentLoaded', function() {

  const body = document.body;
  const pageOverlay = document.getElementById('pageOverlay'); 

  // ============================================================
  // 1. LOGIKA DROPDOWN & NOTIFIKASI
  // ============================================================
  const allNotifWrappers = document.querySelectorAll('.notif-wrapper');
  const allUserWrappers = document.querySelectorAll('.user-wrapper');

  const notifBtn = document.getElementById('notif-btn');
  const notifDropdown = document.getElementById('notifDropdown');
  const userBtn = document.getElementById('user-btn');
  const userDropdown = document.getElementById('userDropdown');

  const notifBtnMobile = document.getElementById('notif-btn-mobile');
  const notifDropdownMobile = document.getElementById('notifDropdownMobile');
  const userBtnMobile = document.getElementById('user-btn-mobile');
  const userDropdownMobile = document.getElementById('userDropdownMobile');

  const userNameEl = document.querySelector('.user-name');
  const userRoleEl = document.querySelector('.user-role');
  if (userDropdown && userNameEl && userRoleEl) {
    userDropdown.setAttribute('data-username', userNameEl.textContent.trim());
    userDropdown.setAttribute('data-role', userRoleEl.textContent.trim());
  }
  if (userDropdownMobile && userNameEl && userRoleEl) {
      userDropdownMobile.setAttribute('data-username', userNameEl.textContent.trim());
      userDropdownMobile.setAttribute('data-role', userRoleEl.textContent.trim());
  }

  function closeAllDropdowns() {
    if (pageOverlay) pageOverlay.classList.remove('active');
    
    allNotifWrappers.forEach(wrapper => {
      if (wrapper.classList.contains('active')) {
        wrapper.classList.remove('active');
        const btn = wrapper.querySelector('button');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        const drop = wrapper.querySelector('.notif-dropdown');
        if (drop) drop.setAttribute('aria-hidden', 'true');
      }
    });
    allUserWrappers.forEach(wrapper => {
      if (wrapper.classList.contains('active')) {
        wrapper.classList.remove('active');
        const btn = wrapper.querySelector('button');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        const drop = wrapper.querySelector('.user-dropdown');
        if (drop) drop.setAttribute('aria-hidden', 'true');
      }
    });
  }

  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const wrapper = notifBtn.closest('.notif-wrapper');
      const isActive = wrapper.classList.contains('active');
      closeAllDropdowns();
      if (!isActive) {
        wrapper.classList.add('active');
        notifBtn.setAttribute('aria-expanded', 'true');
        notifDropdown.setAttribute('aria-hidden', 'false');
        if (pageOverlay) pageOverlay.classList.add('active');
      }
    });
  }
  
  if (notifBtnMobile && notifDropdownMobile) {
    notifBtnMobile.addEventListener('click', function(e) {
      e.stopPropagation();
      const wrapper = notifBtnMobile.closest('.notif-wrapper');
      const isActive = wrapper.classList.contains('active');
      closeAllDropdowns();
      if (!isActive) {
        wrapper.classList.add('active');
        notifBtnMobile.setAttribute('aria-expanded', 'true');
        notifDropdownMobile.setAttribute('aria-hidden', 'false');
        if (pageOverlay) pageOverlay.classList.add('active');
      }
    });
  }

  if (userBtn && userDropdown) {
    userBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const wrapper = userBtn.closest('.user-wrapper');
      const isActive = wrapper.classList.contains('active');
      closeAllDropdowns();
      if (!isActive) {
        wrapper.classList.add('active');
        userBtn.setAttribute('aria-expanded', 'true');
        userDropdown.setAttribute('aria-hidden', 'false');
        if (pageOverlay) pageOverlay.classList.add('active');
      }
    });
  }
  
  if (userBtnMobile && userDropdownMobile) {
    userBtnMobile.addEventListener('click', function(e) {
      e.stopPropagation();
      const wrapper = userBtnMobile.closest('.user-wrapper');
      const isActive = wrapper.classList.contains('active');
      closeAllDropdowns();
      if (!isActive) {
        wrapper.classList.add('active');
        userBtnMobile.setAttribute('aria-expanded', 'true');
        userDropdownMobile.setAttribute('aria-hidden', 'false');
        if (pageOverlay) pageOverlay.classList.add('active');
      }
    });
  }
  
  if (pageOverlay) {
    pageOverlay.addEventListener('click', closeAllDropdowns);
  }

  document.addEventListener('click', function(e) {
    let inside = false;
    allNotifWrappers.forEach(w => { if (w.contains(e.target)) inside = true; });
    allUserWrappers.forEach(w => { if (w.contains(e.target)) inside = true; });
    
    if (!inside) {
      closeAllDropdowns();
    }
  });

  document.querySelectorAll('.notif-dropdown, .user-dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });

  // ============================================================
  // 2. LOGIKA SIDEBAR (DIKEMBALIKAN) - FIX
  // ============================================================
  const btnInSidebar = document.getElementById('btnInSidebar');
  const btnInCol = document.getElementById('btnInCol');
  const iconInSidebar = document.getElementById('iconInSidebar');
  const iconInCol = document.getElementById('iconInCol');
  
  function setCollapsedState(state) {
    if (state) {
      body.classList.add('sidebar-collapsed');
      if (btnInCol) {
        btnInCol.classList.remove('hidden');
        btnInCol.setAttribute('aria-hidden', 'false');
      }
      const wrap = document.querySelector('.internal-wrap');
      if (wrap) wrap.style.display = 'none';
    } else {
      body.classList.remove('sidebar-collapsed');
      if (btnInCol) {
        btnInCol.classList.add('hidden');
        btnInCol.setAttribute('aria-hidden', 'true');
      }
      const wrap = document.querySelector('.internal-wrap');
      if (wrap) wrap.style.display = 'flex';
    }
    try { localStorage.setItem('sidebarCollapsed', state ? 'true' : 'false'); } catch(e){}
    
    if (iconInSidebar) iconInSidebar.style.transform = state ? 'rotate(180deg)' : 'rotate(0deg)';
    if (iconInCol) iconInCol.style.transform = state ? 'rotate(0deg)' : 'rotate(180deg)';
  }

  const initialState = localStorage.getItem('sidebarCollapsed') === 'true';
  setCollapsedState(initialState);

  if (btnInSidebar) {
    btnInSidebar.addEventListener('click', () => setCollapsedState(true));
  }
  if (btnInCol) {
    btnInCol.addEventListener('click', () => setCollapsedState(false));
  }

  // ============================================================
  // 3. LOGIKA OVERVIEW QR CODE (DARI REVISI FINAL)
  // ============================================================
  const ovFlipCard    = document.getElementById('ovFlipCard');
  const ovBtnGenerate = document.getElementById('ovBtnGenerate');
  const ovPlaceholder = document.getElementById('ovPlaceholder');
  const ovQrImgFront  = document.getElementById('ovQrImgFront');
  const ovQrImgBack   = document.getElementById('ovQrImgBack');
  const ovRefreshBtn  = document.getElementById('ovRefreshBtn');
  const ovStatusLabel = document.getElementById('ovStatusLabel');

  const dashData = document.getElementById('dashboard-data');
  const ovUid  = dashData ? dashData.getAttribute('data-uid') : '0';
  const ovPlat = dashData ? dashData.getAttribute('data-plat') : '-----';

  let isOvGenerated = false;
  let isOvFlipped = false; 

  // --- Fungsi Render ---
  function renderOvQR() {
    if (isOvGenerated) return;
    const qrString = `PARKIR_UMK:${ovUid}:${ovPlat}`;
    const imgUrl = `generate_qr.php?text=${encodeURIComponent(qrString)}&v=${Date.now()}`;
    const imgTag = `<img src="${imgUrl}" style="width:100%; height:100%; object-fit:contain; display:block; margin:0 auto;">`;
    
    if(ovQrImgFront) { ovQrImgFront.innerHTML = imgTag; ovQrImgFront.style.display = 'block'; }
    if(ovQrImgBack)  { ovQrImgBack.innerHTML  = imgTag; }
    if(ovPlaceholder) ovPlaceholder.style.display = 'none';
    
    if(ovBtnGenerate) {
        ovBtnGenerate.className = 'ov-btn ov-btn-scan';
        ovBtnGenerate.innerHTML = '<i class="fa-solid fa-expand"></i> SIAP SCAN';
    }
    if(ovStatusLabel) { ovStatusLabel.textContent = 'Ready'; ovStatusLabel.style.color = '#2563eb'; }
    
    isOvGenerated = true;
    sessionStorage.setItem('qr_active_session', 'true');
  }

  // --- Fungsi Reset ---
  function resetOvState() {
    if (!isOvGenerated) return;
    if(ovQrImgFront) { ovQrImgFront.innerHTML = ''; ovQrImgFront.style.display = 'none'; }
    if(ovQrImgBack)  { ovQrImgBack.innerHTML  = ''; }
    if(ovPlaceholder) ovPlaceholder.style.display = 'block';

    if(ovBtnGenerate) {
        ovBtnGenerate.className = 'ov-btn ov-btn-gen';
        ovBtnGenerate.innerHTML = '<i class="fa-solid fa-bolt"></i> GENERATE';
    }
    if(ovStatusLabel) { ovStatusLabel.textContent = 'Inactive'; ovStatusLabel.style.color = '#64748b'; }

    isOvGenerated = false;
    isOvFlipped = false;
    sessionStorage.removeItem('qr_active_session');
  }

  // --- Fungsi Flip ---
  function flipOvBack() {
    if(isOvFlipped || !ovFlipCard) return;
    ovFlipCard.style.transform = "rotateY(180deg)";
    if(ovStatusLabel) { ovStatusLabel.textContent = 'Parked'; ovStatusLabel.style.color = '#166534'; }
    isOvFlipped = true;
  }

  function flipOvFront() {
    if(!isOvFlipped || !ovFlipCard) return;
    ovFlipCard.style.transform = "rotateY(0deg)";
  }

  // --- Polling Status ---
  async function checkOvStatus() {
    if (ovUid === '0') return;
    try {
        const res = await fetch(`check_status.php?user_id=${ovUid}`);
        const data = await res.json();
        
        if (data.success) {
            if (data.status_parkir === 'masuk') {
                if (!isOvGenerated) renderOvQR();
                flipOvBack();
            } else {
                // KASUS KELUAR: Hanya reset jika isOvFlipped = TRUE
                if (isOvFlipped) {
                    flipOvFront();
                    if (isOvGenerated) setTimeout(resetOvState, 600);
                }
            }
        }
    } catch (e) {}
  }

  // --- INITIALIZATION & VALIDATION ---
  if (ovFlipCard) {
  
  // [PENTING] VALIDASI KENDARAAN (Anti-Generate Tanpa Plat)
  if (ovPlat === '-----' || ovPlat === '') {
      // Logika Error State (Jika tidak ada kendaraan)
      resetOvState();
      
      // Pastikan area QR kosong
      if(ovQrImgFront) ovQrImgFront.style.display = 'none';
      
      // Update Placeholder dengan Pesan Error Visual yang Jelas
      if(ovPlaceholder) {
          ovPlaceholder.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #f97316; margin-bottom: 8px;"></i>
                                      <div style="font-size:0.9rem; color:#f97316; font-weight: 700;">DATA KENDARAAN KOSONG</div>
                                      <div style="font-size:0.75rem; color:#64748b;">Silakan pilih atau tambahkan kendaraan.</div>`;
          ovPlaceholder.style.display = 'block';
      }

      // Matikan Tombol Secara Visual & Fungsional
      if(ovBtnGenerate) {
          ovBtnGenerate.innerHTML = '<i class="fa-solid fa-ban"></i> NO VEHICLE';
          ovBtnGenerate.style.background = '#94a3b8';
          ovBtnGenerate.style.pointerEvents = 'none';
          ovBtnGenerate.style.cursor = 'not-allowed';
          ovBtnGenerate.style.boxShadow = 'none';
      }
    } 
    else {
      // Logika Normal (Jika ada kendaraan)
      // Pastikan placeholder kembali ke TAP TO GENERATE jika ada plat
      if(ovPlaceholder) {
          ovPlaceholder.innerHTML = `<i class="fa-solid fa-qrcode" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 8px;"></i>
                                    <div style="font-size:0.8rem; color:#94a3b8;">Tap to Generate</div>`;
      }
      
      if(ovBtnGenerate) ovBtnGenerate.addEventListener('click', (e) => { e.stopPropagation(); renderOvQR(); });
      if(ovRefreshBtn) ovRefreshBtn.addEventListener('click', () => { checkOvStatus(); });

      if (sessionStorage.getItem('qr_active_session') === 'true') {
          renderOvQR();
      }
      checkOvStatus();
      setInterval(checkOvStatus, 3000);
  }
  }
});