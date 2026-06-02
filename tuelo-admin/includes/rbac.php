<?php

// Require admin or superuser — redirect anyone else
function requireAdmin(): void {
    $user = getUser();
    if (!$user || !in_array($user['role_name'], ['admin', 'superuser'])) {
        header('Location: /tuelo-main/index.php');
        exit;
    }
}

// Require superuser only — redirect admins too
function requireSuperuser(): void {
    $user = getUser();
    if (!$user || $user['role_name'] !== 'superuser') {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function logAction(string $action, string $categoryType = '', int $categoryPk = null): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $adminId = $_SESSION['user_id'] ?? null;
    if (!$adminId) return;

    insertIntoDB(
        'INSERT INTO logs (admin_id, action, category_type, category_pk, created_at)
         VALUES (?, ?, ?, ?, NOW())',
        [$adminId, $action, $categoryType ?: null, $categoryPk ?: null]
    );
}