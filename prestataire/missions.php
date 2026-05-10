<?php
include_once __DIR__ . '/_auth.php';
include 'include/header-prestataire.php';

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

    <?php
    $aAccepter = array_values(array_filter($missions, fn($m) => $m['status'] === 'Proposee' && empty($m['id_user'])));
    $mesMissions = array_values(array_filter($missions, fn($m) => !($m['status'] === 'Proposee' && empty($m['id_user']))));
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
                    <?php if (empty($aAccepter)): ?>
                        <p class="text-muted text-center my-3">Aucune mission proposée pour le moment.</p>
                    <?php else: ?>
                        <table class="table align-middle">
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
                                        <button class="btn btn-sm btn-success" <?= !$isProviderValidated ? 'disabled' : '' ?>>Accepter</button>
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
