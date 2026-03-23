<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];
$role = strtolower(trim((string)($user['role'] ?? '')));
if (!in_array($role, ['prestataire', 'provider'], true)) {
    header('Location: ../index.php');
    exit;
}

$providerLink = null;
$providerData = null;
$providerProfile = null;
$currentUserData = $user;
$dbPageError = '';

if (!$pdo instanceof PDO) {
    $dbPageError = 'Base de donnees indisponible.';
} else {
    try {
        $userStmt = $pdo->prepare(
            "SELECT id_user, first_name, last_name, phone, role,
                    company_name, siret_number, validation_status, average_rating, commission_rate,
                    zone, iban, provider_description, skills_text, provider_updated_at
             FROM users
             WHERE id_user = ?
             LIMIT 1"
        );
        $userStmt->execute([$user['id_user'] ?? '']);
        $dbUser = $userStmt->fetch();
        if ($dbUser) {
            $currentUserData = array_merge($currentUserData, $dbUser);
            if (($dbUser['role'] ?? '') === 'prestataire') {
                $providerData = [
                    'id_user' => $dbUser['id_user'],
                    'id_provider' => $dbUser['id_user'],
                    'company_name' => $dbUser['company_name'] ?? null,
                    'siret_number' => $dbUser['siret_number'] ?? null,
                    'validation_status' => $dbUser['validation_status'] ?? null,
                    'average_rating' => $dbUser['average_rating'] ?? null,
                    'commission_rate' => $dbUser['commission_rate'] ?? null,
                ];
                $providerProfile = [
                    'zone' => $dbUser['zone'] ?? null,
                    'iban' => $dbUser['iban'] ?? null,
                    'description' => $dbUser['provider_description'] ?? null,
                    'skills_text' => $dbUser['skills_text'] ?? null,
                    'updated_at' => $dbUser['provider_updated_at'] ?? null,
                ];
            }
        }
    } catch (PDOException $e) {
        $dbPageError = 'Erreur base de donnees: ' . $e->getMessage();
    }
}

if (!function_exists('sh_provider_is_validated')) {
    function sh_provider_is_validated($status)
    {
        $s = strtolower(trim((string)$status));
        $s = str_replace(['é', 'è', 'ê', 'ë'], 'e', $s);
        return in_array($s, ['valide', 'validee', 'valid'], true);
    }
}

$isProviderValidated = $providerData ? sh_provider_is_validated($providerData['validation_status'] ?? '') : false;
