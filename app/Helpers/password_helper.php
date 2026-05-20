<?php

if (! function_exists('password_validation_error')) {
    /**
     * Validasi password + konfirmasi.
     *
     * @return string|null Pesan error, atau null jika valid
     */
    function password_validation_error(?string $password, ?string $confirm, bool $required = false): ?string
    {
        $password = trim((string) $password);
        $confirm  = trim((string) $confirm);

        if ($required && $password === '') {
            return 'Password wajib diisi.';
        }

        if ($password === '') {
            return null;
        }

        if (strlen($password) < 8) {
            return 'Password minimal 8 karakter.';
        }

        $hasLetter  = preg_match('/[a-zA-Z]/', $password);
        $hasNumber  = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        if (!$hasLetter || (!$hasNumber && !$hasSpecial)) {
            return 'Password harus berupa kombinasi huruf dengan angka atau karakter unik.';
        }

        if ($confirm === '') {
            return 'Konfirmasi password wajib diisi.';
        }

        if ($password !== $confirm) {
            return 'Password dan konfirmasi password tidak sama.';
        }

        return null;
    }
}
