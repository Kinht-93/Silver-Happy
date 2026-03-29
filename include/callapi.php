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
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return ['error' => 'Impossible de se connecter à l\'API'];
    }
    return json_decode($response, true);
}
