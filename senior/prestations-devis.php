<?php
$seniorCurrent = 'prestations';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$quotes = [];

if ($token !== '' && $userId !== '') {
    $response = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/quotes', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $quotes = $response;
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Mes devis</h1>
        <div class="senior-panel">
            <?php if (empty($quotes)): ?>
                <p class="mb-0">Aucun devis disponible pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Prestation</th>
                                <th>Date</th>
                                <th>Intervention</th>
                                <th>Montant TTC</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($quote['quote_number'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($quote['prestation_name'] ?? 'Prestation')) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)($quote['created_at'] ?? 'now')))) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)($quote['desired_date'] ?? 'now')))) ?></td>
                                    <td><?= number_format((float)($quote['amount_incl_tax'] ?? 0), 2, ',', ' ') ?> EUR</td>
                                    <td><?= htmlspecialchars((string)($quote['status'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
