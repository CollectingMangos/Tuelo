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