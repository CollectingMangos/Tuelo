<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!empty($_SESSION['user_cache'])) {
        return $_SESSION['user_cache'];
    }

    $user = singleQueryDB(
        'SELECT u.*, r.name AS role_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         WHERE u.id = ? AND u.is_verified = 1',
        [$_SESSION['user_id']]
    );

    if ($user) {
        $_SESSION['user_cache'] = $user;
    }

    return $user ?: null;
}

function isLoggedIn(): bool {
    return getUser() !== null;
}

function requireLogin(string $loginPage = '/tuelo-main/login.php'): void {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . $loginPage . '?redirect=' . $redirect);
        exit;
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_cache'] = null;
}

function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}

function hasRole(string $role): bool {
    $user = getUser();
    return $user && $user['role_name'] === $role;
}

function hasPermission(string $permissionName): bool {
    $user = getUser();

    if (!$user) {
        return false;
    }

    if ($user['role_name'] === 'superuser') {
        return true;
    }

    $row = singleQueryDB(
        'SELECT rp.role_id
         FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id = ? AND p.name = ?',
        [$user['role_id'], $permissionName]
    );

    return $row !== null;
}

function redirectIfLoggedIn(string $destination = '/tuelo-main/index.php'): void {
    if (isLoggedIn()) {
        header('Location: ' . $destination);
        exit;
    }
}