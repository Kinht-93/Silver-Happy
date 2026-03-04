<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'silverhappy';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
$charset = 'utf8mb4';

if ($pass === false) {
    $pass = '';
}

$portCandidates = [];
$envPort = getenv('DB_PORT');
if ($envPort !== false && $envPort !== '') {
    $portCandidates[] = (int)$envPort;
}

$portCandidates[] = 3306;
$portCandidates[] = 8889;
$portCandidates = array_values(array_unique($portCandidates));

$credentialCandidates = [
    ['user' => $user, 'pass' => $pass],
];

if ($user === 'root' && $pass === '') {
    $credentialCandidates[] = ['user' => 'root', 'pass' => 'root'];
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = null;
    $lastError = null;

    foreach ($portCandidates as $port) {
        foreach ($credentialCandidates as $credentials) {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
                $pdo = new PDO($dsn, $credentials['user'], $credentials['pass'], $options);
                break 2;
            } catch (PDOException $e) {
                $lastError = $e;
            }
        }
    }

    if (!$pdo instanceof PDO) {
        throw $lastError ?: new PDOException('Connexion MySQL impossible.');
    }
} catch (PDOException $e) {
    $pdo = null;
    $dbError = 'Erreur de connexion à la base de données.';
    if (function_exists('error_log')) {
        error_log('SilverHappy DB error: ' . $e->getMessage());
    }
}
?>
