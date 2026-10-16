<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (attempt_login($username, $password)) {
            set_flash('success', 'Selamat datang di ' . APP_NAME . '.');
            redirect('dashboard.php');
        }
        $error = 'Username atau password salah, atau akun tidak aktif.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-control:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
        }
        .btn-login {
            background-color: #0284c7;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background-color: #0369a1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
        }
        .partner-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            filter: grayscale(15%);
            transition: transform 0.2s ease, filter 0.2s ease;
        }
        .partner-logo:hover {
            filter: grayscale(0%);
            transform: scale(1.08);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card login-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="mx-auto mb-3">
                        <img src="<?= e(url('assets/img/komputer.png')) ?>"
                             alt="Laboratorium Komputer"
                             style="width: 72px; height: 72px; object-fit: contain;">
                    </div>
                    <h1 class="h4 fw-bold text-dark mb-1"><?= e(APP_SHORT_NAME) ?></h1>
                    <p class="text-muted small mb-0">Laboratorium Komputer SMKN 3 Soppeng</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
                <?php endif; ?>
                <?= flash() ?>

                <form method="post" autocomplete="off" class="mb-4">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <button class="btn btn-login btn-primary w-100 py-2 fw-semibold" type="submit">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
                    </button>
                </form>

                <hr class="text-muted opacity-25 my-3">

                <div class="text-center">
                    <small class="text-muted d-block mb-2" style="font-size: 0.75rem;">Didukung & Dikembangkan Bersama:</small>
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <img src="<?= e(url('assets/img/Logo UNIPOL.png')) ?>" alt="Logo UNIPOL" class="partner-logo" title="Universitas Lamappapoleonro">
                        <img src="<?= e(url('assets/img/logosmk.jpg')) ?>" alt="Logo SMKN 3" class="partner-logo rounded-circle" title="SMKN 3 Soppeng">
                        <img src="<?= e(url('assets/img/kampus_berdampak_logo.jpg')) ?>" alt="Logo Kampus Berdampak" class="partner-logo" title="Program Berdampak">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
