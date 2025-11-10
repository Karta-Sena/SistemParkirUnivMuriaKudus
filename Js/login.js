history.scrollRestoration = 'manual';
document.addEventListener('DOMContentLoaded', () => {
    
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', () => {
            const targetInputId = button.dataset.target;
            const targetInput = document.getElementById(targetInputId);

            if (targetInput) {
                const isPassword = targetInput.type === 'password';
                targetInput.type = isPassword ? 'text' : 'password';
                button.textContent = isPassword ? '🙉' : '🙈';
            }
        });
    });

});