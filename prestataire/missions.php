<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providerData && $token !== '') {
    $action = $_POST['action'] ?? '';
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour accepter des missions.');
        }

        if ($action === 'accept') {
            $idMission = trim((string)($_POST['id_mission'] ?? ''));
            $response = callAPI('http://localhost:8080/api/provider-missions/' . urlencode($idMission) . '/accept', 'POST', [
                'id_user' => $providerData['id_user'],
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                $message = 'Mission acceptee.';
                $messageType = 'success';
            } else {
                throw new RuntimeException((string)($response['error'] ?? 'Mission indisponible ou deja acceptee.'));
            }
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$missions = [];
if ($providerData && $token !== '') {
    $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-missions', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $missions = $response;
    } else {
        $message = 'Erreur: ' . (string)($response['error'] ?? 'Impossible de charger les missions.');
        $messageType = 'danger';
    }
}

$basePath = '../';
include '../include/header.php';
?>

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

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Missions disponibles et assignees</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Titre</th><th>Date</th><th>Description</th><th>Statut</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($missions)): ?>
                        <tr><td colspan="5" class="text-center">Aucune mission pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($missions as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['mission_title']) ?></td>
                            <td><?= htmlspecialchars((string)($m['mission_date'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($m['mission_description'] ?? '')) ?></td>
                            <td>
                                <?php if ($m['status'] === 'Acceptee'): ?>
                                    <span class="badge bg-success">Acceptee</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Proposee</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['status'] === 'Proposee' && empty($m['id_user'])): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="id_mission" value="<?= htmlspecialchars($m['id_mission']) ?>">
                                    <button class="btn btn-sm btn-primary" <?= !$isProviderValidated ? 'disabled' : '' ?>>Accepter</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>
