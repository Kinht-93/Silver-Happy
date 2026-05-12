<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';
require_once __DIR__ . '/../active_user.php';

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

updateUserActivity();

$providerLink = null;
$providerData = null;
$providerProfile = null;
$currentUserData = $user;
$dbPageError = '';
$token = (string)($user['token'] ?? '');

if ($token === '') {
    $dbPageError = 'Connexion API indisponible.';
} else {
    $userResponse = callAPI('http://localhost:8080/api/users/' . urlencode((string)($user['id_user'] ?? '')), 'GET', null, $token);
    if (!is_array($userResponse) || isset($userResponse['error'])) {
        $dbPageError = 'Erreur API: ' . (string)($userResponse['error'] ?? 'Impossible de charger le profil prestataire.');
    } else {
        $currentUserData = array_merge($currentUserData, $userResponse);
        if (($userResponse['role'] ?? '') === 'prestataire') {
            $providerData = [
                'id_user' => $userResponse['id_user'],
                'id_provider' => $userResponse['id_user'],
                'company_name' => $userResponse['company_name'] ?? null,
                'siret_number' => $userResponse['siret_number'] ?? null,
                'validation_status' => $userResponse['validation_status'] ?? null,
                'average_rating' => $userResponse['average_rating'] ?? null,
                'commission_rate' => $userResponse['commission_rate'] ?? null,
            ];
            $providerProfile = [
                'zone' => $userResponse['zone'] ?? null,
                'iban' => $userResponse['iban'] ?? null,
                'description' => $userResponse['provider_description'] ?? null,
                'skills_text' => $userResponse['skills_text'] ?? null,
                'updated_at' => $userResponse['provider_updated_at'] ?? null,
            ];
        }
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
