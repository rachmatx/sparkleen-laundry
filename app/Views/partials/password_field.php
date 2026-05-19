<?php
/**
 * Input password dengan tombol reveal (ikon mata).
 *
 * Variabel:
 * - $id, $name, $placeholder, $value (opsional)
 * - $label (opsional, teks label)
 * - $required (bool)
 * - $helpText (opsional)
 * - $wrapperClass (default: mb-3)
 */
$id           = $id ?? 'password';
$name         = $name ?? 'password';
$placeholder  = $placeholder ?? '';
$value        = $value ?? '';
$label        = $label ?? null;
$required     = ! empty($required);
$helpText     = $helpText ?? null;
$wrapperClass = $wrapperClass ?? 'mb-3';
$autocomplete = $autocomplete ?? 'new-password';
?>
<div class="<?= esc($wrapperClass) ?>">
    <?php if ($label !== null): ?>
    <label class="form-label fw-semibold" for="<?= esc($id) ?>">
        <?= esc($label) ?><?= $required ? ' <span class="text-danger">*</span>' : '' ?>
    </label>
    <?php endif; ?>
    <div class="input-group password-toggle-group">
        <input
            type="password"
            class="form-control"
            id="<?= esc($id) ?>"
            name="<?= esc($name) ?>"
            placeholder="<?= esc($placeholder) ?>"
            value="<?= esc($value) ?>"
            <?= $required ? 'required' : '' ?>
            autocomplete="<?= esc($autocomplete) ?>"
        >
        <button
            type="button"
            class="btn btn-outline-secondary password-toggle-btn"
            data-password-target="<?= esc($id) ?>"
            aria-label="Tampilkan password"
            tabindex="-1"
        >
            <i class="fas fa-eye" aria-hidden="true"></i>
        </button>
    </div>
    <?php if ($helpText): ?>
    <div class="form-text text-muted"><?= esc($helpText) ?></div>
    <?php endif; ?>
</div>
