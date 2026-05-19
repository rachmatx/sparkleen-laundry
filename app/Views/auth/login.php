<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sparkleen Laundry</title>
    
    <!-- Google Font: Inter (Premium Modern Font) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" crossorigin="anonymous">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi latar belakang (Glassmorphism circle) */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(13,110,253,0.1), rgba(13,110,253,0.02));
            z-index: 0;
        }
        .bg-circle-1 { width: 400px; height: 400px; top: -100px; left: -100px; }
        .bg-circle-2 { width: 600px; height: 600px; bottom: -150px; right: -150px; }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.03);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            text-align: center;
            padding: 35px 30px 20px;
        }

        .login-logo {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .login-logo span {
            color: #0d6efd;
        }
        
        .login-subtitle {
            font-size: 14px;
            color: #6c757d;
            font-weight: 400;
        }

        .login-body {
            padding: 10px 35px 35px;
        }

        .form-floating > .form-control {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding-left: 45px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .form-floating > .form-control:focus {
            background-color: #fff;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .form-floating > label {
            padding-left: 45px;
            color: #888;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
            font-size: 16px;
        }

        .password-toggle-floating > .form-control {
            padding-right: 48px;
        }
        .password-toggle-btn-login {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 6;
            border: none;
            background: transparent;
            color: #6c757d;
            padding: 0.35rem 0.5rem;
            line-height: 1;
        }
        .password-toggle-btn-login:hover {
            color: #0d6efd;
        }

        .btn-login {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #0b5ed7, #094eb8);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
            transform: translateY(-2px);
        }

        /* Notifikasi Modern */
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 14px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }
        .alert-danger { background-color: #ffe6e6; color: #cc0000; }
        .alert-success { background-color: #e6f9eb; color: #009933; }
        .alert i { margin-right: 10px; font-size: 16px; }
    </style>
</head>
<body>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <a href="/" class="login-logo"><span>Sparkleen</span> Laundry</a>
                <div class="login-subtitle">Masuk untuk mengelola bisnis Anda</div>
            </div>
            
            <div class="login-body">
                <?php if (! empty($loginBlocked) && ! session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="fas fa-shield-alt"></i>
                        <div><?= esc($loginBlockedMessage ?? '') ?></div>
                    </div>
                <?php endif; ?>

                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= session()->getFlashdata('error') ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success mb-4" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <div><?= session()->getFlashdata('success') ?></div>
                    </div>
                <?php endif; ?>

                <form action="/doLogin" method="post"<?= ! empty($loginBlocked) ? ' class="pe-none opacity-75"' : '' ?>>
                    <?= csrf_field() ?>
                    <!-- Username / Email Field -->
                    <div class="form-floating mb-3 position-relative">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username atau Email" required autocomplete="username"<?= ! empty($loginBlocked) ? ' disabled' : '' ?>>
                        <label for="username">Username / Email</label>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-floating mb-4 position-relative password-toggle-floating">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="current-password"<?= ! empty($loginBlocked) ? ' disabled' : '' ?>>
                        <label for="password">Password</label>
                        <button type="button" class="password-toggle-btn-login" data-password-target="password" aria-label="Tampilkan password">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary btn-login"<?= ! empty($loginBlocked) ? ' disabled' : '' ?>>
                            <?= ! empty($loginBlocked) ? 'Login Terkunci' : 'Masuk' ?> <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4" style="color: #999; font-size: 12px; z-index: 1;">
            &copy; <?= date('Y') ?> Sparkleen Laundry. All rights reserved.
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/js/password-toggle.js"></script>
</body>
</html>