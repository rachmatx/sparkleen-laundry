/**
 * Toggle visibility untuk input password (ikon mata).
 * Tombol harus memiliki atribut data-password-target="id-input"
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-password-target');
            const input = document.getElementById(targetId);
            if (!input) {
                return;
            }

            const icon = btn.querySelector('i');
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';

            if (icon) {
                icon.classList.toggle('fa-eye', !showPassword);
                icon.classList.toggle('fa-eye-slash', showPassword);
            }

            btn.setAttribute(
                'aria-label',
                showPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmInput = form.querySelector('input[name="password_confirm"]');
        if (!passwordInput || !confirmInput) {
            return;
        }

        form.addEventListener('submit', function (event) {
            const password = passwordInput.value.trim();
            const confirm = confirmInput.value.trim();

            if (password === '' && confirm === '') {
                return;
            }

            if (password !== confirm) {
                event.preventDefault();
                confirmInput.setCustomValidity('Password dan konfirmasi password tidak sama.');
                confirmInput.reportValidity();
                return;
            }

            if (password.length > 0 && password.length < 6) {
                event.preventDefault();
                passwordInput.setCustomValidity('Password minimal 6 karakter.');
                passwordInput.reportValidity();
                return;
            }

            confirmInput.setCustomValidity('');
            passwordInput.setCustomValidity('');
        });

        [passwordInput, confirmInput].forEach(function (input) {
            input.addEventListener('input', function () {
                input.setCustomValidity('');
            });
        });
    });
});
