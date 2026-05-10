<?php

require_once __DIR__ . '/include/callapi.php';

function updateUserActivity() {
    $sessionUser = $_SESSION['user'] ?? $_SESSION['Users'] ?? null;
    if (!is_array($sessionUser)) {
        return;
    }

    $userId = (string)($sessionUser['id_user'] ?? '');
    $token = (string)($sessionUser['token'] ?? '');
    if ($userId === '' || $token === '') {
        return;
    }

    callAPI('hhttp://silverhappy_api:8080/api/users/' . urlencode($userId) . '/activity', 'POST', [], $token);
}
?> 