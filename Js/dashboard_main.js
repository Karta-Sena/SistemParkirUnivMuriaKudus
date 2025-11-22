// FILE: Js/dashboard_main.js
// REVISI FINAL: Fix Tombol 'Tandai Baca' & Fix Icon Keluar

document.addEventListener('DOMContentLoaded', function() {

  const body = document.body;
  const pageOverlay = document.getElementById('pageOverlay'); 

  // ============================================================
  // 1. LOGIKA DROPDOWN UI (User & Notif)
  // ============================================================
  const notifBtn = document.getElementById('notif-btn');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifWrapper = document.querySelector('.notif-wrapper');
  
  const userBtn = document.getElementById('user-btn');
  const userDropdown = document.getElementById('userDropdown');
  const userWrapper = document.querySelector('.user-wrapper');

  // Versi Mobile
  const notifBtnMobile = document.getElementById('notif-btn-mobile');
  const notifDropdownMobile = document.getElementById('notifDropdownMobile');
  const notifWrapperMobile = notifBtnMobile ? notifBtnMobile.closest('.notif-wrapper') : null;

  const userBtnMobile = document.getElementById('user-btn-mobile');
  const userDropdownMobile = document.getElementById('userDropdownMobile');
  const userWrapperMobile = userBtnMobile ? userBtnMobile.closest('.user-wrapper') : null;

  const allNotifWrappers = document.querySelectorAll('.notif-wrapper');
  const allUserWrappers = document.querySelectorAll('.user-wrapper');

  // [PERBAIKAN 1] Tambahkan Listener untuk Tombol "Tandai semua telah dibaca"
  const markReadBtns = document.querySelectorAll('.mark-as-read-btn');
  markReadBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
          e.stopPropagation(); // Agar dropdown tidak menutup saat tombol diklik
          if (typeof markNotificationsAsRead === 'function') {
              markNotificationsAsRead(); // Panggil fungsi reset dot merah
          }
      });
  });

  // Helper: Tutup semua dropdown
  function closeAllDropdowns() {
    allNotifWrappers.forEach(el => {
        el.classList.remove('active');
        const drop = el.querySelector('.notif-dropdown');
        if(drop) drop.setAttribute('aria-hidden', 'true');
    });
    allUserWrappers.forEach(el => {
        el.classList.remove('active');
        const drop = el.querySelector('.user-dropdown');
        if(drop) drop.setAttribute('aria-hidden', 'true');
    });
    if (pageOverlay) pageOverlay.classList.remove('active');
  }

  // Helper: Toggle Dropdown
  function toggleDropdown(wrapper, btn, dropdown) {
    const isActive = wrapper.classList.contains('active');
    closeAllDropdowns(); // Tutup yang lain dulu
    
    if (!isActive) {
      wrapper.classList.add('active');
      dropdown.setAttribute('aria-hidden', 'false');
      if (pageOverlay) pageOverlay.classList.add('active');

      // Opsional: Jika ingin otomatis tandai baca saat dibuka, uncomment ini:
      /*
      if (wrapper.classList.contains('notif-wrapper')) {
          if (typeof markNotificationsAsRead === 'function') {
              markNotificationsAsRead();
          }
      }
      */
    }
  }

  // Event Listeners Desktop
  if (notifBtn) notifBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(notifWrapper, notifBtn, notifDropdown); });
  if (userBtn) userBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(userWrapper, userBtn, userDropdown); });

  // Event Listeners Mobile
  if (notifBtnMobile) notifBtnMobile.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(notifWrapperMobile, notifBtnMobile, notifDropdownMobile); });
  if (userBtnMobile) userBtnMobile.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(userWrapperMobile, userBtnMobile, userDropdownMobile); });

  // Close on click outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.notif-wrapper') && !e.target.closest('.user-wrapper')) {
      closeAllDropdowns();
    }
  });
  if (pageOverlay) pageOverlay.addEventListener('click', closeAllDropdowns);


  // ============================================================
  // 2. SIDEBAR LOGIC
  // ============================================================
  const btnInSidebar = document.getElementById('btnInSidebar');
  const btnInCol = document.getElementById('btnInCol');
  
  function setCollapsedState(state) {
    if (state) {
      body.classList.add('sidebar-collapsed');
      if (btnInCol) btnInCol.classList.remove('hidden');
      const wrap = document.querySelector('.internal-wrap');
      if (wrap) wrap.style.display = 'none';
    } else {
      body.classList.remove('sidebar-collapsed');
      if (btnInCol) btnInCol.classList.add('hidden');
      const wrap = document.querySelector('.internal-wrap');
      if (wrap) wrap.style.display = 'flex';
    }
    try { localStorage.setItem('sidebarCollapsed', state ? 'true' : 'false'); } catch(e){}
  }

  const initialState = localStorage.getItem('sidebarCollapsed') === 'true';
  setCollapsedState(initialState);

  if (btnInSidebar) btnInSidebar.addEventListener('click', () => setCollapsedState(true));
  if (btnInCol) btnInCol.addEventListener('click', () => setCollapsedState(false));


  // ============================================================
  // 3. OVERVIEW QR LOGIC
  // ============================================================
  const ovFlipCard    = document.getElementById('ovFlipCard');
  const ovBtnGenerate = document.getElementById('ovBtnGenerate');
  const ovPlaceholder = document.getElementById('ovPlaceholder');
  const ovQrImgFront  = document.getElementById('ovQrImgFront');
  const ovQrImgBack   = document.getElementById('ovQrImgBack');
  const ovRefreshBtn  = document.getElementById('ovRefreshBtn');
  const ovStatusLabel = document.getElementById('ovStatusLabel');

  const dashData = document.getElementById('dashboard-data');
  const ovUid  = dashData ? (dashData.getAttribute('data-uid') || '0').trim() : '0';
  const ovPlat = dashData ? (dashData.getAttribute('data-plat') || '').trim() : '-----';

  let isOvGenerated = false;
  let isOvFlipped = false; 

  function renderOvQR() {
    if (isOvGenerated) return;
    const qrString = `PARKIR_UMK:${ovUid}:${ovPlat}`;
    const imgUrl = `generate_qr.php?text=${encodeURIComponent(qrString)}&v=${Date.now()}`;
    const imgTag = `<img src="${imgUrl}" style="width:100%; height:100%; object-fit:contain; display:block; margin:0 auto; border-radius:8px;">`;
    
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
                if (isOvFlipped) {
                    flipOvFront();
                    if (isOvGenerated) setTimeout(resetOvState, 600);
                }
            }
        }
    } catch (e) {}
  }

  if (ovFlipCard) {
      if (ovPlat === '-----' || ovPlat === '') {
          resetOvState();
          if(ovQrImgFront) ovQrImgFront.style.display = 'none';
          if(ovPlaceholder) {
              ovPlaceholder.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #f97316; margin-bottom: 8px;"></i>
                                          <div style="font-size:0.9rem; color:#f97316; font-weight: 700;">DATA KENDARAAN KOSONG</div>
                                          <div style="font-size:0.75rem; color:#64748b;">Silakan pilih atau tambahkan kendaraan.</div>`;
              ovPlaceholder.style.display = 'block';
          }
          if(ovBtnGenerate) {
              ovBtnGenerate.innerHTML = '<i class="fa-solid fa-ban"></i> NO VEHICLE';
              ovBtnGenerate.style.background = '#94a3b8';
              ovBtnGenerate.style.pointerEvents = 'none';
              ovBtnGenerate.style.cursor = 'not-allowed';
              ovBtnGenerate.style.boxShadow = 'none';
          }
      } else {
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


  // ============================================================
  // 4. [FITUR UTAMA] REAL-TIME NOTIFICATION (Dot Merah & Icon Fix)
  // ============================================================
  
  const badges = document.querySelectorAll('.notif-badge'); 
  const dropdownBodies = document.querySelectorAll('.notif-dropdown .dropdown-body');

  // Fungsi Global untuk menandai sudah dibaca
  window.markNotificationsAsRead = function() {
      fetch('get_notifications.php?action=mark_read')
          .then(response => response.json())
          .then(res => {
              if (res.status === 'success') {
                  // Sembunyikan dot merah
                  badges.forEach(badge => badge.style.display = 'none');
                  
                  // Hapus highlight background di list item
                  document.querySelectorAll('.notif-item').forEach(item => {
                      item.style.background = 'transparent';
                  });
              }
          })
          .catch(err => console.log('Err marking read:', err));
  };

  function fetchNotifications() {
      fetch('get_notifications.php?action=fetch')
          .then(response => response.json())
          .then(res => {
              if (res.status === 'success') {
                  updateNotificationUI(res.unread, res.data);
              }
          })
          .catch(err => console.error('Gagal mengambil notifikasi:', err));
  }

  function updateNotificationUI(unreadCount, messages) {
      // 1. Update Dot Merah
      badges.forEach(badge => {
          if (unreadCount > 0) {
              badge.style.display = 'block';
              badge.style.transform = 'scale(1)';
          } else {
              badge.style.display = 'none';
          }
      });

      // 2. Update Isi Dropdown
      let html = '';
      
      if (messages.length === 0) {
          html = `
            <div style="text-align:center; padding:20px; color:#9ca3af;">
                <i class="fa-regular fa-bell-slash" style="font-size:1.5rem; margin-bottom:8px;"></i>
                <div style="font-size:0.85rem;">Tidak ada notifikasi baru</div>
            </div>`;
      } else {
          messages.forEach(msg => {
              // [PERBAIKAN 2] Ganti Logic Icon Keluar
              let icon = 'fa-circle-info';
              let colorClass = '#2563eb'; // Biru
              
              // Case-insensitive check
              const msgText = (msg.message || "").toLowerCase();

              if (msg.type === 'masuk' || msgText.includes('masuk')) {
                  icon = 'fa-square-parking';
                  colorClass = '#16a34a'; // Hijau
              } 
              else if (msg.type === 'keluar' || msgText.includes('keluar')) {
                  // GANTI ICON KELUAR YANG LEBIH UMUM
                  icon = 'fa-arrow-right-from-bracket'; 
                  colorClass = '#dc2626'; // Merah
              } 
              else if (msg.type === 'pindah_area') {
                  icon = 'fa-share-from-square';
                  colorClass = '#ca8a04'; // Kuning
              }

              // Highlight background jika belum dibaca
              const unreadStyle = (msg.is_read == 0) ? 'background: rgba(37, 99, 235, 0.05);' : '';

              html += `
              <a href="#" class="notif-item" style="${unreadStyle}">
                  <div class="notif-icon" style="color:${colorClass}; margin-right:12px; font-size:1.1rem;">
                    <i class="fa-solid ${icon}"></i>
                  </div>
                  <div class="notif-content">
                      <div class="notif-title" style="font-size:0.85rem; margin-bottom:2px; font-weight:600;">
                        ${msg.message}
                      </div>
                      <div class="notif-text" style="font-size:0.7rem; color:#6b7280;">
                        ${msg.formatted_time}
                      </div>
                  </div>
              </a>`;
          });
      }

      dropdownBodies.forEach(body => {
          body.innerHTML = html;
      });
  }

  // Jalankan Polling
  fetchNotifications(); 
  setInterval(fetchNotifications, 3000);

});