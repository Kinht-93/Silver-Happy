<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'prestations';
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$requests = [];
$loadError = '';

if ($token !== '' && $userId !== '') {
    $requestsResponse = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/service-requests', 'GET', null, $token);
    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories', 'GET', null, $token);

    if (
        is_array($requestsResponse) && !isset($requestsResponse['error']) &&
        is_array($categoriesResponse) && !isset($categoriesResponse['error'])
    ) {
        $categoriesById = [];
        foreach ($categoriesResponse as $category) {
            $categoriesById[(string)($category['id_service_category'] ?? '')] = (string)($category['name'] ?? 'Prestation');
        }
        foreach ($requestsResponse as $request) {
            $request['category_name'] = $categoriesById[(string)($request['id_service_category'] ?? '')] ?? 'Prestation';
            $requests[] = $request;
        }
    } else {
        $loadError = 'Impossible de charger vos demandes.';
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Mes demandes</h1>

        <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
            <div class="alert alert-success" role="alert">Votre demande de prestation a ete enregistree.</div>
        <?php endif; ?>

        <?php if ($loadError): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($loadError) ?></div>
        <?php endif; ?>

        <div class="senior-panel">
            <?php if (empty($requests)): ?>
                <p class="mb-0">Aucune demande disponible pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Prestation</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Duree</th>
                                <th>Adresse</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$request['category_name']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)$request['desired_date']))) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$request['start_time'], 0, 5)) ?></td>
                                    <td><?= (int)$request['estimated_duration'] ?> h</td>
                                    <td><?= htmlspecialchars((string)$request['intervention_address']) ?></td>
                                    <td><?= htmlspecialchars((string)$request['status']) ?></td>
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
