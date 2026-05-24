<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    logoutUser();
}

header('Location: /tuelo-main/login.php?logged_out=1');
exit;