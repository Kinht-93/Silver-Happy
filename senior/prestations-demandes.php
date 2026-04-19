<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'prestations';
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$requests = [];
$loadError = '';

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $stmt = $pdo->prepare(
            "SELECT sr.id_request, sr.desired_date, sr.start_time, sr.estimated_duration, sr.intervention_address, sr.status, sr.created_at,
                    sc.name AS category_name
             FROM service_requests sr
             INNER JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
             WHERE sr.id_user = ?
             ORDER BY sr.created_at DESC"
        );
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll();
    } catch (PDOException $e) {
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
