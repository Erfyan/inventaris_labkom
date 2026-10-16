<?php
require_once __DIR__ . '/functions.php';

function current_user(){
    return $_SESSION['user'] ?? null;
}

function is_logged_in(){
    return current_user() !== null;
}

function attempt_login($username, $password){
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND status = "aktif" LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'nama' => $user['nama'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];

    $stmt = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    return true;
}

function logout_user(){
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(){
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function has_role($roles){
    $user = current_user();
    if (!$user) {
        return false;
    }
    $roles = (array) $roles;
    return in_array($user['role'], $roles, true);
}

function require_role($roles){
    require_login();
    if (!has_role($roles)) {
        set_flash('danger', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        redirect('dashboard.php');
    }
}

function can_manage_data(){
    return has_role(['admin', 'petugas']);
}
