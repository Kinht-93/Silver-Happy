<?php
function callAPI($url, $method = 'GET', $data = null, $token = '') {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "X-Token: {$token}\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($opts);

    $response = @file_get_contents($url, false, $context);
    $http_response_header = $http_response_header ?? [];

    $status_code = 200;
    if (!empty($http_response_header)) {
        $status_line = $http_response_header[0];
        if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $status_line, $matches)) {
            $status_code = (int)$matches[1];
        }
    }

    if ($response === false) {
        return ['error' => 'Impossible de se connecter à l\'API', 'status_code' => $status_code];
    }

    $decoded = json_decode($response, true);

    if ($status_code === 401 && is_array($decoded) && isset($decoded['error'])) {
        if (strpos(strtolower($decoded['error']), 'token expired') !== false ||
            strpos(strtolower($decoded['error']), 'invalid token') !== false) {
            handleTokenExpiration();
            return ['error' => 'Session expirée. Veuillez vous reconnecter.', 'token_expired' => true, 'status_code' => $status_code];
        }
    }

    return $decoded;
}

function handleTokenExpiration() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_unset();
    session_destroy();

    if (!headers_sent()) {
        header('Location: /login.php?expired=1');
        exit;
    } else {
        echo '<script type="text/javascript">
            window.location.href = "/login.php?expired=1";
        </script>';
        echo '<noscript>
            <meta http-equiv="refresh" content="0;url=/login.php?expired=1">
        </noscript>';
        echo '<p>Votre session a expiré. <a href="/login.php?expired=1">Cliquez ici pour vous reconnecter</a>.</p>';
        exit;
    }
}

