<?php
$seniorCurrent = 'prestations';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$services = [];

if ($token !== '' && $userId !== '') {
    $response = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/completed-services', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $services = $response;
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Prestations réalisées</h1>
        <div class="senior-panel">
            <?php if (empty($services)): ?>
                <p class="mb-0">Aucune prestation réalisée pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Prestation</th>
                                <th>Date</th>
                                <th>Horaire</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($service['prestation_name'] ?? 'Prestation')) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)($service['service_date'] ?? 'now')))) ?></td>
                                    <td><?= htmlspecialchars(substr((string)($service['start_time'] ?? ''), 0, 5)) ?> - <?= htmlspecialchars(substr((string)($service['end_time'] ?? ''), 0, 5)) ?></td>
                                    <td><?= number_format((float)($service['senior_amount'] ?? 0), 2, ',', ' ') ?> EUR</td>
                                    <td><?= htmlspecialchars((string)($service['status'] ?? '')) ?></td>
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
