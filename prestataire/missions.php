<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO && $providerData) {
    $action = $_POST['action'] ?? '';
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour accepter des missions.');
        }

        if ($action === 'accept') {
            $idMission = trim((string)($_POST['id_mission'] ?? ''));
            $stmt = $pdo->prepare(
                "UPDATE provider_missions
                                 SET status = 'Acceptee', id_user = ?, accepted_at = NOW()
                 WHERE id_mission = ?
                                     AND (id_user IS NULL OR id_user = ?)
                   AND status = 'Proposee'"
            );
                        $stmt->execute([$providerData['id_user'], $idMission, $providerData['id_user']]);

            if ($stmt->rowCount() > 0) {
                $message = 'Mission acceptee.';
                $messageType = 'success';
            } else {
                $message = 'Mission indisponible ou deja acceptee.';
                $messageType = 'warning';
            }
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$missions = [];
if ($pdo instanceof PDO && $providerData) {
    try {
        $stmt = $pdo->prepare(
            "SELECT id_mission, mission_title, mission_description, mission_date, status, id_user
             FROM provider_missions
             WHERE id_user IS NULL OR id_user = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$providerData['id_user']]);
        $missions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $message = 'Erreur: ' . $e->getMessage();
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
