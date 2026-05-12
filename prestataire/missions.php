<?php
include_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../db.php';

$message = $_SESSION['provider_mission_message'] ?? '';
$messageType = $_SESSION['provider_mission_message_type'] ?? '';
unset($_SESSION['provider_mission_message'], $_SESSION['provider_mission_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providerData && $token !== '') {
    $action = $_POST['action'] ?? '';
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour accepter des missions.');
        }

        if ($action === 'accept') {
            $idMission = trim((string)($_POST['id_mission'] ?? ''));
            $response = callAPI('http://silverhappy_api:8080/api/provider-missions/' . urlencode($idMission) . '/accept', 'POST', [
                'id_user' => $providerData['id_user'],
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                $_SESSION['provider_mission_message'] = 'Mission acceptee.';
                $_SESSION['provider_mission_message_type'] = 'success';
                header("Location: {$_SERVER['PHP_SELF']}");
                exit;
            } else {
                throw new RuntimeException((string)($response['error'] ?? 'Mission indisponible ou deja acceptee.'));
            }
        } elseif ($action === 'accept_request') {
            $requestId = trim((string)($_POST['id_request'] ?? ''));
            if ($requestId === '') {
                throw new RuntimeException('Demande invalide.');
            }
            if (!$pdo instanceof PDO) {
                throw new RuntimeException('Base de donnees indisponible.');
            }

            $requestStmt = $pdo->prepare(
                "SELECT sr.id_request, sr.desired_date, sr.start_time, sr.estimated_duration,
                        sr.intervention_address, sr.id_user, sr.id_service_category,
                        COALESCE(sc.name, '') AS category_name,
                        COALESCE(u.first_name, '') AS first_name,
                        COALESCE(u.last_name, '') AS last_name
                 FROM service_requests sr
                 LEFT JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
                 LEFT JOIN users u ON u.id_user = sr.id_user
                 WHERE sr.id_request = ?
                   AND sr.status = 'En attente'
                   AND EXISTS (
                        SELECT 1
                        FROM provider_service_categories psc
                        WHERE psc.id_user = ?
                          AND psc.id_service_category = sr.id_service_category
                   )
                 LIMIT 1"
            );
            $requestStmt->execute([$requestId, (string)$providerData['id_user']]);
            $requestRow = $requestStmt->fetch();
            if (!$requestRow) {
                throw new RuntimeException('Cette demande n est plus disponible ou ne correspond pas a vos categories.');
            }

            $updateStmt = $pdo->prepare("UPDATE service_requests SET status = 'Acceptee' WHERE id_request = ? AND status = 'En attente'");
            $updateStmt->execute([$requestId]);
            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException('Cette demande vient d etre prise en charge.');
            }

            $seniorName = trim((string)($requestRow['first_name'] ?? '') . ' ' . (string)($requestRow['last_name'] ?? ''));
            if ($seniorName === '') {
                $seniorName = (string)($requestRow['id_user'] ?? 'N/A');
            }

            $categoryName = trim((string)($requestRow['category_name'] ?? ''));
            if ($categoryName === '') {
                $categoryName = 'Service';
            }

            $missionDescription = 'Senior: ' . $seniorName
                . ' | Service: ' . $categoryName
                . ' | Heure: ' . substr((string)($requestRow['start_time'] ?? ''), 0, 5)
                . ' | Duree: ' . (int)($requestRow['estimated_duration'] ?? 0) . 'h'
                . ' | Adresse: ' . (string)($requestRow['intervention_address'] ?? '');

            $insertStmt = $pdo->prepare(
                "INSERT INTO provider_missions (id_mission, mission_title, mission_description, mission_date, status, id_user, accepted_at, created_at)
                 VALUES (CONCAT('mis_', UUID()), ?, ?, ?, 'Acceptee', ?, NOW(), NOW())"
            );
            $insertStmt->execute([
                'Demande senior - ' . $categoryName,
                $missionDescription,
                (string)($requestRow['desired_date'] ?? null),
                (string)$providerData['id_user'],
            ]);

            $_SESSION['provider_mission_message'] = 'Demande senior acceptee.';
            $_SESSION['provider_mission_message_type'] = 'success';
            header("Location: {$_SERVER['PHP_SELF']}");
            exit;
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$missions = [];
if ($providerData && $token !== '') {
    $response = callAPI('http://silverhappy_api:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-missions', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $missions = $response;
    } else {
        $message = 'Erreur: ' . (string)($response['error'] ?? 'Impossible de charger les missions.');
        $messageType = 'danger';
    }
}

$seniorRequests = [];
if ($providerData && $pdo instanceof PDO) {
    try {
        $categoryStmt = $pdo->prepare(
            "SELECT id_service_category FROM provider_service_categories WHERE id_user = ?"
        );
        $categoryStmt->execute([(string)$providerData['id_user']]);
        $providerCategoryIds = array_values(array_filter(array_map(
            static fn($row) => (string)($row['id_service_category'] ?? ''),
            $categoryStmt->fetchAll() ?: []
        )));

        if (!empty($providerCategoryIds)) {
            $placeholders = implode(',', array_fill(0, count($providerCategoryIds), '?'));
            $requestsSql = "
                SELECT sr.id_request, sr.desired_date, sr.start_time, sr.estimated_duration,
                       sr.intervention_address, sr.status, sr.created_at, sr.id_user,
                       sr.id_service_category, COALESCE(sc.name, '') AS category_name,
                       COALESCE(u.first_name, '') AS first_name, COALESCE(u.last_name, '') AS last_name
                FROM service_requests sr
                LEFT JOIN users u ON u.id_user = sr.id_user
                LEFT JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
                WHERE sr.status = 'En attente'
                  AND sr.id_service_category IN ($placeholders)
                ORDER BY sr.created_at DESC
            ";
            $requestsStmt = $pdo->prepare($requestsSql);
            $requestsStmt->execute($providerCategoryIds);
            $seniorRequests = $requestsStmt->fetchAll() ?: [];
        }
    } catch (Exception $e) {
        $seniorRequests = [];
    }
}

$basePath = '../';
?>

<?php include 'include/header-prestataire.php'; ?>

<div class="page-title h3 mb-3">Missions</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>" role="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!$providerData): ?>
<div class="alert alert-warning" role="alert">Aucune fiche prestataire associee.</div>
<?php else: ?>
    <?php if (!$isProviderValidated): ?>
    <div class="alert alert-warning" role="alert">Compte non valide: acceptation des missions bloquee.</div>
    <?php endif; ?>

    <?php
    $aAccepter = array_values(array_filter($missions, fn($m) => ($m['status'] ?? '') === 'Proposee'));
    $mesMissions = array_values(array_filter($missions, fn($m) => ($m['status'] ?? '') !== 'Proposee'));
    ?>

    <ul class="nav nav-tabs mb-3" id="missionsTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProposees">
                À accepter
                <?php if (!empty($aAccepter)): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= count($aAccepter) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMesMissions">
                Mes missions
                <?php if (!empty($mesMissions)): ?>
                    <span class="badge bg-primary ms-1"><?= count($mesMissions) ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="tabProposees">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Demandes seniors par categorie</h6>
                    <?php if (empty($seniorRequests)): ?>
                        <p class="text-muted text-center my-3">Aucune demande senior en attente pour vos categories.</p>
                    <?php else: ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Senior</th><th>Categorie</th><th>Date</th><th>Heure</th><th>Duree</th><th>Adresse</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach ($seniorRequests as $sr): ?>
                                <tr>
                                    <td><?= htmlspecialchars(trim((string)($sr['first_name'] ?? '') . ' ' . (string)($sr['last_name'] ?? '')) ?: (string)($sr['id_user'] ?? 'N/A')) ?></td>
                                    <td><?= htmlspecialchars((string)($sr['category_name'] ?? (string)($sr['id_service_category'] ?? 'N/A'))) ?></td>
                                    <td><?= htmlspecialchars(!empty($sr['desired_date']) ? date('d/m/Y', strtotime((string)$sr['desired_date'])) : '—') ?></td>
                                    <td><?= htmlspecialchars(substr((string)($sr['start_time'] ?? ''), 0, 5)) ?></td>
                                    <td><?= (int)($sr['estimated_duration'] ?? 0) ?> h</td>
                                    <td><?= htmlspecialchars((string)($sr['intervention_address'] ?? '—')) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Accepter cette demande ?');">
                                            <input type="hidden" name="action" value="accept_request">
                                            <input type="hidden" name="id_request" value="<?= htmlspecialchars((string)($sr['id_request'] ?? '')) ?>">
                                            <button class="btn btn-sm btn-success py-0 px-2" <?= !$isProviderValidated ? 'disabled' : '' ?>>Accepter</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <h6 class="mb-3">Missions a accepter</h6>
                    <?php if (empty($aAccepter)): ?>
                        <p class="text-muted text-center my-3">Aucune mission proposée pour le moment.</p>
                    <?php else: ?>
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Titre</th><th>Date</th><th>Statut</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($aAccepter as $m): ?>
                            <tr>
                                <td>
                                    <span role="button" class="text-primary" onclick="voirMission(<?= htmlspecialchars(json_encode($m)) ?>)">
                                        <?= htmlspecialchars($m['mission_title']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string)($m['mission_date'] ?? '—')) ?></td>
                                <td><span class="badge bg-warning text-dark">Proposée</span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="id_mission" value="<?= htmlspecialchars($m['id_mission']) ?>">
                                        <button class="btn btn-sm btn-success py-0 px-2" <?= !$isProviderValidated ? 'disabled' : '' ?>>Accepter</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabMesMissions">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($mesMissions)): ?>
                        <p class="text-muted text-center my-3">Vous n'avez encore accepté aucune mission.</p>
                    <?php else: ?>
                        <table class="table align-middle">
                            <thead><tr><th>Titre</th><th>Date</th><th>Statut</th></tr></thead>
                            <tbody>
                            <?php foreach ($mesMissions as $m): ?>
                            <tr>
                                <td>
                                    <span role="button" class="text-primary" onclick="voirMission(<?= htmlspecialchars(json_encode($m)) ?>)">
                                        <?= htmlspecialchars($m['mission_title']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string)($m['mission_date'] ?? '—')) ?></td>
                                <td>
                                    <?php if ($m['status'] === 'Acceptee'): ?>
                                        <span class="badge bg-success">Acceptée</span>
                                    <?php elseif ($m['status'] === 'Terminee'): ?>
                                        <span class="badge bg-secondary">Terminée</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($m['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal détail mission -->
    <div class="modal fade" id="modalMission" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="missionModalTitre"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Date :</strong> <span id="missionModalDate"></span></p>
                    <p><strong>Description :</strong></p>
                    <p id="missionModalDesc" class="text-muted"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include '../include/footer.php'; ?>

<script>
function voirMission(m) {
    document.getElementById('missionModalTitre').textContent = m.mission_title || '';
    document.getElementById('missionModalDate').textContent  = m.mission_date  || '—';
    document.getElementById('missionModalDesc').textContent  = m.mission_description || 'Aucune description.';
    new bootstrap.Modal(document.getElementById('modalMission')).show();
}
</script>
