const modal = document.getElementById('formModal');
const modalTitle = document.getElementById('modalTitle');
const closeBtn = document.querySelector('.close-btn');
const vehicleForm = document.getElementById('vehicleForm');

if (modal && vehicleForm) {
    const formKendaraanId = document.getElementById('form-kendaraan-id');
    const formPlatNomor = document.getElementById('form-plat-nomor');
    const formNoStnk = document.getElementById('form-no-stnk');

    function openModal() {
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
        vehicleForm.reset();
        formKendaraanId.value = '';
    }

    const tambahBtn = document.getElementById('tambahBtn');
    if (tambahBtn) {
        tambahBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Tambah Kendaraan Baru';
            openModal();
        });
    }

    const daftarKendaraan = document.getElementById('daftar-kendaraan');
    if (daftarKendaraan) {
        daftarKendaraan.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-edit')) {
                const btn = e.target;
                modalTitle.textContent = 'Edit Kendaraan';
                formKendaraanId.value = btn.dataset.id;
                formPlatNomor.value = btn.dataset.plat;
                formNoStnk.value = btn.dataset.stnk;
                openModal();
            }
        });
    }

    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }
    
    window.onclick = (event) => {
        if (event.target == modal) {
            closeModal();
        }
    }

    vehicleForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const isEditing = formKendaraanId.value !== '';
        const url = isEditing ? 'update_kendaraan.php' : 'tambah_kendaraan.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); 
                closeModal();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan. ' + error);
        });
    });
}

function hapusKendaraan(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus kendaraan ini?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);

    fetch('hapus_kendaraan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            const vehicleElement = document.getElementById('kendaraan-' + id);
            if (vehicleElement) {
                vehicleElement.remove();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan. ' + error);
    });
}

document.addEventListener('DOMContentLoaded', () => {

    const profileToggle = document.getElementById('profile-toggle');
    const profileToggleMobile = document.getElementById('profile-toggle-mobile');
    const desktopMenu = document.querySelector('.profile-dropdown');
    const mobileMenu = document.querySelector('.profile-drawup');
    const closeBtnDrawup = document.getElementById('close-drawup');

    const overlay = document.querySelector('.overlay');
    
    const notifToggleDesktop = document.getElementById('notif-toggle-desktop');
    const notifToggleMobile = document.getElementById('notif-toggle-mobile');
    const notifPanel = document.getElementById('notification-panel');
    const closeNotifBtn = document.getElementById('close-notif');

    function closeAllMenus() {
        if(desktopMenu) desktopMenu.classList.remove('active');
        if(mobileMenu) mobileMenu.classList.remove('active');
        if(notifPanel) notifPanel.classList.remove('active');
        if(overlay) overlay.classList.remove('active');
    }
    
    function toggleProfileMenu(e) {
        e.preventDefault();
        if (!desktopMenu || !mobileMenu || !overlay) return;

        const isProfileActive = desktopMenu.classList.contains('active') || mobileMenu.classList.contains('active');
        const wasNotifActive = notifPanel ? notifPanel.classList.contains('active') : false;
        
        if (!isProfileActive) {
            if(wasNotifActive) closeAllMenus();
            desktopMenu.classList.add('active');
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
        } else {
            closeAllMenus();
        }
    }

    function toggleNotificationMenu(e) {
        e.preventDefault();
        if (!notifPanel || !overlay) return;

        const isNotifActive = notifPanel.classList.contains('active');
        const wasProfileActive = (desktopMenu ? desktopMenu.classList.contains('active') : false) || (mobileMenu ? mobileMenu.classList.contains('active') : false);

        if (!isNotifActive) {
            if(wasProfileActive) closeAllMenus();
            notifPanel.classList.add('active');
            overlay.classList.add('active');
        } else {
            closeAllMenus();
        }
    }

    if(profileToggle) profileToggle.addEventListener('click', toggleProfileMenu);
    if(profileToggleMobile) profileToggleMobile.addEventListener('click', toggleProfileMenu);

    if(notifToggleDesktop) notifToggleDesktop.addEventListener('click', toggleNotificationMenu);
    if(notifToggleMobile) notifToggleMobile.addEventListener('click', toggleNotificationMenu);

    if(closeBtnDrawup) closeBtnDrawup.addEventListener('click', closeAllMenus);
    if(closeNotifBtn) closeNotifBtn.addEventListener('click', closeAllMenus);
    if(overlay) overlay.addEventListener('click', closeAllMenus);

    window.addEventListener('click', (e) => {
        if (profileToggle && desktopMenu &&
            !profileToggle.contains(e.target) && !desktopMenu.contains(e.target) && !desktopMenu.contains(e.target)) {
            desktopMenu.classList.remove('active');
        }
        
        if (notifToggleDesktop && notifPanel &&
            !notifToggleDesktop.contains(e.target) && !notifPanel.contains(e.target) && !notifPanel.contains(e.target)) {
            notifPanel.classList.remove('active');
        }
    });
});