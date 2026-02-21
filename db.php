<?php
// Connexion à la base de données MySQL
$host = 'localhost';
$db = 'silverhappy';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En cas d'erreur, on garde le site fonctionnel et on log simplement le problème.
    $pdo = null;
    $dbError = 'Erreur de connexion à la base de données.';
    if (function_exists('error_log')) {
        error_log('SilverHappy DB error: ' . $e->getMessage());
    }
}
?>
