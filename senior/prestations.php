<?php
$seniorCurrent = 'prestations';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$catalogueCount = 0;
$requestsCount = 0;
$quotesCount = 0;
$completedCount = 0;

if ($token !== '' && $userId !== '') {
    $catalogue = callAPI('http://localhost:8080/api/service-types', 'GET', null, $token);
    $requests = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/service-requests', 'GET', null, $token);
    $quotes = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/quotes', 'GET', null, $token);
    $completed = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/completed-services', 'GET', null, $token);

    $catalogueCount = is_array($catalogue) && !isset($catalogue['error']) ? count($catalogue) : 0;
    $requestsCount = is_array($requests) && !isset($requests['error']) ? count($requests) : 0;
    $quotesCount = is_array($quotes) && !isset($quotes['error']) ? count($quotes) : 0;
    $completedCount = is_array($completed) && !isset($completed['error']) ? count($completed) : 0;
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Prestations</h1>
        <p class="senior-subtitle">Accédez aux différentes rubriques de prestations.</p>

        <div class="senior-grid">
            <a class="senior-card" href="prestations-catalogue.php"><h2>Catalogue des prestations</h2><p><?= $catalogueCount ?> service<?= $catalogueCount > 1 ? 's' : '' ?> disponible<?= $catalogueCount > 1 ? 's' : '' ?>.</p></a>
            <a class="senior-card" href="prestations-demande.php"><h2>Demander une prestation</h2><p>Envoyez une nouvelle demande.</p></a>
            <a class="senior-card" href="prestations-demandes.php"><h2>Mes demandes</h2><p><?= $requestsCount ?> demande<?= $requestsCount > 1 ? 's' : '' ?> enregistrée<?= $requestsCount > 1 ? 's' : '' ?>.</p></a>
            <a class="senior-card" href="prestations-devis.php"><h2>Mes devis</h2><p><?= $quotesCount ?> devis disponible<?= $quotesCount > 1 ? 's' : '' ?>.</p></a>
            <a class="senior-card" href="prestations-realisees.php"><h2>Prestations réalisées</h2><p><?= $completedCount ?> intervention<?= $completedCount > 1 ? 's' : '' ?> terminée<?= $completedCount > 1 ? 's' : '' ?>.</p></a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
