history.scrollRestoration = 'manual';
document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('register-form');
    const role = document.getElementById('role');
    const nama = document.getElementById('nama');
    const nim = document.getElementById('nim');
    const nidn = document.getElementById('nidn');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const konfirmasiPass = document.getElementById('konfirmasi-pass');
    const noStnk = document.getElementById('no-stnk');
    const platNomor = document.getElementById('plat-nomor');

    const nimGroup = document.getElementById('nim-group');
    const nidnGroup = document.getElementById('nidn-group');
    const statusMessage = document.getElementById('form-status-message');
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    const inputs = [role, nama, nim, nidn, email, password, konfirmasiPass, noStnk, platNomor];

    function showError(inputId, message) {
        const input = document.getElementById(inputId);
        const error = document.getElementById(inputId + '-error');
        if (input && error) {
            input.classList.add('input-error');
            error.textContent = message;
        }
    }

    function clearErrors() {
        statusMessage.textContent = '';
        statusMessage.className = '';
        inputs.forEach(input => {
            if (input) input.classList.remove('input-error');
            const error = document.getElementById(input.id + '-error');
            if (error) error.textContent = '';
        });
    }

    function isValidEmail(email) {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function showStatus(message, type = 'error') {
        statusMessage.textContent = message;
        statusMessage.className = type; 
    }

    function handleFieldToggle() {
        const selectedRole = role.value;
        nimGroup.classList.add('field-hidden');
        nidnGroup.classList.add('field-hidden');
        nim.required = false;
        nidn.required = false;

        if (selectedRole === 'mahasiswa') {
            nimGroup.classList.remove('field-hidden');
            nim.required = true;
        } else if (selectedRole === 'dosen') {
            nidnGroup.classList.remove('field-hidden');
            nidn.required = true;
        }
    }

    function handlePasswordToggle(event) {
        const button = event.currentTarget;
        const targetInputId = button.dataset.target;
        const targetInput = document.getElementById(targetInputId);

        if (targetInput) {
            const isPassword = targetInput.type === 'password';
            targetInput.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? '🙉' : '🙈';
        }
    }

    function validateForm() {
        let hasError = false;
        
        if (role.value === "") { hasError = true; showError('role', 'Role harus dipilih.'); }
        if (nama.value.trim() === "") { hasError = true; showError('nama', 'Nama tidak boleh kosong.'); }
        if (noStnk.value.trim() === "") { hasError = true; showError('no-stnk', 'Nomor STNK tidak boleh kosong.'); }
        if (platNomor.value.trim() === "") { hasError = true; showError('plat-nomor', 'Plat nomor tidak boleh kosong.'); }

        if (email.value.trim() === "") {
            hasError = true; showError('email', 'Email tidak boleh kosong.');
        } else if (!isValidEmail(email.value.trim())) {
            hasError = true; showError('email', 'Format email tidak valid.');
        }

        if (password.value.trim() === "") {
            hasError = true; showError('password', 'Password tidak boleh kosong.');
        } else if (password.value.trim().length < 6) {
            hasError = true; showError('password', 'Password minimal 6 karakter.');
        }

        if (konfirmasiPass.value.trim() === "") {
            hasError = true; showError('konfirmasi-pass', 'Konfirmasi password tidak boleh kosong.');
        } else if (password.value.trim() !== konfirmasiPass.value.trim()) {
            hasError = true; showError('konfirmasi-pass', 'Password tidak cocok.');
        }

        if (role.value === 'mahasiswa' && nim.value.trim() === "") {
            hasError = true; showError('nim', 'NIM tidak boleh kosong.');
        }
        if (role.value === 'dosen' && nidn.value.trim() === "") {
            hasError = true; showError('nidn', 'NIDN tidak boleh kosong.');
        }

        return !hasError; 
    }

    function handleFormSubmit(event) {
        event.preventDefault();
        clearErrors();

        if (validateForm()) {
            showStatus('Registrasi Berhasil! Data sedang diproses.', 'success');
            setTimeout(() => {
                form.submit();
            }, 1000);
        } else {
            showStatus('Gagal mendaftar. Silakan periksa kembali data Anda.', 'error');
        }
    }
    
    role.addEventListener('change', handleFieldToggle);
    togglePasswordButtons.forEach(button => button.addEventListener('click', handlePasswordToggle));
    form.addEventListener('submit', handleFormSubmit);

    handleFieldToggle();
});