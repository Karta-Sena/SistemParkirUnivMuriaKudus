<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Parkir UMK</title>
  
  <link rel="stylesheet" href="Css/dashboard_layout.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- ========== SIDEBAR (DESKTOP) ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="internal-wrap" aria-hidden="false">
      <button id="btnInSidebar" class="btn-circle" aria-label="Collapse sidebar" title="Collapse sidebar" type="button">
        <svg id="iconInSidebar" viewBox="0 0 24 24" fill="none" aria-hidden>
          <path d="M15 6 L9 12 L15 18" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <div class="sidebar-logo">
      <img src="Lambang UMK.png" alt="Logo UMK" />
    </div>
    
    <nav class="sidebar-nav">
      <a href="#" class="nav-item active">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
        <span class="nav-text">Overview</span>
      </a>
      <a href="#" class="nav-item">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
        <span class="nav-text">QR Code</span>
      </a>
      <a href="#" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
        <span class="nav-text">Kendaraan</span>
      </a>
      <a href="#" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
        <span class="nav-text">Riwayat Parkir</span>
      </a>
    </nav>
  </aside>

  <!-- ========== TOMBOL EXPAND (Muncul saat collapsed) ========== -->
  <div class="btn-col" id="btnCol" aria-hidden="true">
    <button id="btnInCol" class="btn-circle hidden" aria-label="Expand sidebar" title="Expand sidebar" type="button">
      <svg id="iconInCol" viewBox="0 0 24 24" fill="none" aria-hidden>
        <path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- ========== BOTTOM NAV (MOBILE) ========== -->
  <nav class="bottom-nav">
    <a href="#" class="nav-item active">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
      <span>Overview</span>
    </a>
    <a href="#" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
      <span>QR Code</span>
    </a>
    <a href="#" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
      <span>Kendaraan</span>
    </a>
    <a href="#" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
      <span>Riwayat Parkir</span>
    </a>
  </nav>

  <!-- ========== HEADER ========== -->
  <header class="top-header">
    <div class="logo-mobile">
      <img src="Lambang UMK.png" alt="Logo UMK" />
    </div>
    <div class="header-parent header-parent-desktop" aria-hidden="false">
      <div class="notif-wrapper">
        <button class="header-child-notif" id="notif-btn" aria-label="Notifikasi" aria-expanded="false" aria-controls="notifDropdown">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-badge" aria-hidden="true"></span>
        </button>
        <div class="notif-dropdown" id="notifDropdown" aria-hidden="true">
          <div class="dropdown-header">
            <h3>Notifikasi</h3>
            <button class="mark-as-read-btn">Tandai semua telah dibaca</button>
          </div>
          <div class="dropdown-body">
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Parkir Berhasil</div>
                <div class="notif-text">Anda parkir di B-01 (Teknik).</div>
              </div>
            </a>
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Sistem</div>
                <div class="notif-text">QR Code Anda akan segera kedaluwarsa.</div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-profile" id="user-btn" aria-label="Profil Pengguna" aria-expanded="false" aria-controls="userDropdown">
          <img src="assets/img/avatar.png" alt="Avatar" class="avatar-placeholder avatar">
          <div class="user-text user-info">
            <span class="user-name">User Testing</span>
            <span class="user-role">Mahasiswa</span>
          </div>
          <span class="caret">▼</span>
        </button>
        <div class="user-dropdown" id="userDropdown" aria-hidden="true" data-username="User Testing" data-role="Mahasiswa">
          <div class="dropdown-body">
            <a href="#" class="dropdown-item">
              <i class="fa-solid fa-user-circle"></i>
              <span>Profil Saya</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item danger">
              <i class="fa-solid fa-sign-out-alt"></i>
              <span>Keluar</span>
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="header-parent-mobile" aria-hidden="true">
      <div class="notif-wrapper">
        <button class="header-child-button" aria-label="Notifikasi" id="notif-btn-mobile">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-badge" aria-hidden="true"></span>
        </button>
        <div class="notif-dropdown" id="notifDropdownMobile" aria-hidden="true">
          <div class="dropdown-header">
            <h3>Notifikasi</h3>
            <button class="mark-as-read-btn">Tandai semua telah dibaca</button>
          </div>
          <div class="dropdown-body">
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Parkir Berhasil</div>
                <div class="notif-text">Anda parkir di B-01 (Teknik).</div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-button" aria-label="Profil Pengguna" id="user-btn-mobile">
          <div class="avatar-placeholder-mobile" role="img" aria-label="Avatar"></div>
        </button>
        <div class="user-dropdown" id="userDropdownMobile" aria-hidden="true" data-username="User Testing" data-role="Mahasiswa">
          <div class="dropdown-body">
            <a href="#" class="dropdown-item">
              <i class="fa-solid fa-user-circle"></i>
              <span>Profil Saya</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item danger">
              <i class="fa-solid fa-sign-out-alt"></i>
              <span>Keluar</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ========== MAIN CONTENT ========== -->
  <main class="main-content" id="mainContent">
    <div class="page-content">
      <div class="main-parent-container">
        <section class="dashboard">
          <article class="card card-qr">
            <div class="card-header">
              <div class="card-title">
                <strong>QR CODE</strong>
                <span class="status-label">Status QR: (-----)</span>
              </div>
              <button class="card-icon-btn" id="qrRefreshBtn" aria-label="refresh QR">
                ⟳
              </button>
            </div>
            <div class="card-content-qr" id="qrBox">[ QR CODE ]</div>
          </article>
          <article class="card card-lokasi">
            <div class="card-content">
              <h3 class="card-title card-title-lokasi">Lokasi Parkir Saat Ini</h3>
              <div class="lokasi-detail">
                <span class="lokasi-kode">B1</span>
                <span class="lokasi-nama">(GEDUNG TEKNIK)</span>
              </div>
            </div>
          </article>
          <article class="card card-kendaraan">
            <div class="card-content">
              <h3 class="card-title">Kendaraan Yang Digunakan</h3>
              <div class="vehicle-display">
                <img src="Css/Assets/3D Modeling Scooter.png" alt="Skuter 3D" class="vehicle-image">
              </div>
              <div class="vehicle-details-list">
                <div class="detail-item">
                  <span>STNK: (-----)</span>
                </div>
                <div class="detail-item">
                  <span>Plat Nomor: (-----)</span>
                </div>
                <div class="detail-item">
                  <span>Jenis: (-----)</span>
                </div>
              </div>
            </div>
          </article>
          <article class="card card-riwayat">
            <div class="card-content">
              <h3 class="card-title">Riwayat Parkir Terakhir</h3>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Plat Nomor</th>
                      <th>Masuk</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>U 1212 T</td>
                      <td>07:45</td>
                      <td><span class="status-badge status-out">Keluar</span></td>
                    </tr>
                    <tr>
                      <td>H 8787 Z</td>
                      <td>10:20</td>
                      <td><span class="status-badge status-parked">Terparkir</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </article>
        </section>
      </div>
    </div>
  </main>
  
  <script src="Js/dashboard_main.js" defer></script>
  
  <div class="page-overlay" id="pageOverlay"></div>

</body>
</html>