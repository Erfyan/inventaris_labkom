<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_login();
$user = current_user();
$activePage = basename($_SERVER['PHP_SELF']);

function nav_item($file, $label, $icon, $show = true){
    global $activePage;
    if (!$show) {
        return '';
    }
    $active = $activePage === $file ? 'active' : '';
    return '<li class="nav-item"><a class="nav-link ' . $active . '" href="' . e(url($file)) . '"><i class="nav-icon bi ' . e($icon) . '"></i><p>' . e($label) . '</p></a></li>';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OverlayScrollbars/1.13.1/css/OverlayScrollbars.min.css">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?= e(url('dashboard.php')) ?>" class="brand-link">
            <img src="<?= e(url('assets/img/logosmk.jpg')) ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?= e(APP_NAME) ?></span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="<?= e(url('assets/img/logosmk.jpg')) ?>" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?= e($user['nama'] ?? '') ?></a>
                    <span class="text-white-50 small"><?= e(ucwords(str_replace('_', ' ', $user['role'] ?? ''))) ?></span>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <?= nav_item('dashboard.php', 'Dashboard', 'bi-speedometer2') ?>
                    <?= nav_item('barang.php', 'Data Barang', 'bi-box-seam') ?>
                    <?= nav_item('stok.php', 'Transaksi Stok', 'bi-arrow-left-right', can_manage_data()) ?>
                    <?= nav_item('peminjaman.php', 'Peminjaman', 'bi-journal-arrow-up') ?>
                    <?= nav_item('mutasi.php', 'Mutasi Ruangan', 'bi-building-gear', can_manage_data()) ?>
                    <?= nav_item('perawatan.php', 'Perawatan', 'bi-tools') ?>
                    <?= nav_item('kategori.php', 'Kategori', 'bi-tags') ?>
                    <?= nav_item('ruangan.php', 'Ruangan/Lab', 'bi-door-open') ?>
                    <?= nav_item('supplier.php', 'Supplier', 'bi-truck') ?>
                    <?= nav_item('users.php', 'Pengguna', 'bi-people', has_role('admin')) ?>
                    <?= nav_item('laporan.php', 'Laporan', 'bi-file-earmark-text') ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= e(url('dashboard.php')) ?>" class="nav-link">Dashboard</a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown user-menu">
                    <a id="userMenuBtn" href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="<?= e(url('assets/img/logosmk.jpg')) ?>" class="user-image img-circle elevation-1" alt="User Image" style="width:28px;height:28px;object-fit:cover;margin-right:.5rem;">
                        <span class="d-none d-md-inline"><?= e($user['nama'] ?? 'Administrator') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenuBtn">
                        <span class="dropdown-item-text small text-muted px-3">Role: <?= e(ucwords(str_replace('_', ' ', $user['role'] ?? 'admin'))) ?></span>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right mr-2"></i>Logout</a>
                    </div>
                </li>
            </ul>
        </nav>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= e($pageTitle ?? APP_NAME) ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= e(url('dashboard.php')) ?>">Home</a></li>
                            <li class="breadcrumb-item active"><?= e($pageTitle ?? 'Dashboard') ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?= flash() ?>

