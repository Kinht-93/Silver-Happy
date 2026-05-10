<?php
include './include/header-admin.php';
require_once __DIR__ . '/../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';

$devis = [];
$seniors = [];
$serviceTypes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!empty($token)) {
        if ($action === 'create') {
            $response = callAPI('http://localhost:8080/api/admin-quotes', 'POST', [
                'id_user' => $_POST['id_user'] ?? '',
                'id_service_type' => $_POST['id_service_type'] ?? '',
                'amount' => isset($_POST['amount']) ? (float) $_POST['amount'] : 0,
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                $message = "Devis créé avec succès.";
                $messageType = "success";
            } else {
                $message = 'Erreur sur la gestion du devis: ' . ($response['error'] ?? 'Création impossible.');
                $messageType = 'danger';
            }
        } elseif ($action === 'update' && !empty($_POST['id'])) {
            $response = callAPI('http://localhost:8080/api/admin-quotes/' . urlencode($_POST['id']), 'PATCH', [
                'amount' => isset($_POST['amount']) ? (float) $_POST['amount'] : 0,
                'status' => $_POST['status'] ?? 'En attente',
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                $message = "Devis mis à jour.";
                $messageType = "success";
            } else {
                $message = 'Erreur sur la gestion du devis: ' . ($response['error'] ?? 'Mise à jour impossible.');
                $messageType = 'danger';
            }
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            $response = callAPI('http://localhost:8080/api/admin-quotes/' . urlencode($_POST['id']), 'DELETE', null, $token);
            if (!is_array($response) || !isset($response['error'])) {
                $message = "Devis supprimé.";
                $messageType = "success";
            } else {
                $message = 'Erreur sur la gestion du devis: ' . $response['error'];
                $messageType = 'danger';
            }
        }
    }
}

if (!empty($token)) {
    $devisResponse = callAPI('http://localhost:8080/api/admin-quotes', 'GET', null, $token);
    $seniorsResponse = callAPI('http://localhost:8080/api/users-summary?roles=senior', 'GET', null, $token);
    $serviceTypesResponse = callAPI('http://localhost:8080/api/service-types', 'GET', null, $token);

    if (is_array($devisResponse) && !isset($devisResponse['error'])) {
        $devis = $devisResponse;
    } elseif ($message === '') {
        $message = 'Erreur lors du chargement des devis.';
        $messageType = 'danger';
    }

    if (is_array($seniorsResponse) && !isset($seniorsResponse['error'])) {
        $seniors = array_values(array_filter($seniorsResponse, static function ($user) {
            return !empty($user['active']);
        }));
    }

    if (is_array($serviceTypesResponse) && !isset($serviceTypesResponse['error'])) {
        $serviceTypes = $serviceTypesResponse;
    }
} elseif ($message === '') {
    $message = 'Token d\'authentification manquant.';
    $messageType = 'danger';
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des devis</div>

<div class="row mb-4">
    <div class="col">
        <input type="text" class="form-control" style="max-width: 250px;" placeholder="Rechercher un devis...">
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddQuote">+ Créer un devis</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Devis</th>
                    <th>Senior</th>
                    <th>Prestation</th>
                    <th>Montant</th>
                    <th>Date création</th>
                    <th>Date validité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devis)): ?>
                    <tr><td colspan="8" class="text-center">Aucun devis trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($devis as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['quote_number']) ?></strong></td>
                            <td><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></td>
                            <td><?= htmlspecialchars($d['prestation_name'] ?: 'Non définie') ?></td>
                            <td><?= number_format($d['amount'], 2) ?>€</td>
                            <td><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['created_at'] . ' + 30 days')) ?></td>
                            <td>
                                <?php if ($d['status'] == 'Accepté'): ?>
                                    <span class="badge bg-success">Accepté</span>
                                <?php elseif ($d['status'] == 'Refusé'): ?>
                                    <span class="badge bg-danger">Refusé</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= htmlspecialchars($d['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-quote="<?= htmlspecialchars(json_encode($d)) ?>"
                                    onclick="viewQuote(this)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-quote="<?= htmlspecialchars(json_encode($d)) ?>"
                                    onclick="editQuote(this)"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce devis ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($d['id_quote']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="modalAddQuote" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer un devis</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddQuote">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="quoteSenior" class="form-label">Senior *</label>
                        <select class="form-control" id="quoteSenior" name="id_user" required>
                            <option value="">Sélectionner un senior</option>
                            <?php foreach ($seniors as $senior): ?>
                                <option value="<?= htmlspecialchars($senior['id_user']) ?>">
                                    <?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quoteService" class="form-label">Prestation *</label>
                        <select class="form-control" id="quoteService" name="id_service_type" required>
                            <option value="">Sélectionner une prestation</option>
                            <?php foreach ($serviceTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type['id_service_type']) ?>">
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quoteAmount" class="form-label">Montant TTC (€) *</label>
                        <input type="number" class="form-control" id="quoteAmount" name="amount" step="0.01" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddQuote" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditQuote" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le devis</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditQuote">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editQuoteId" name="id">
                    <div class="mb-3">
                        <label for="editQuoteNumber" class="form-label">N° Devis</label>
                        <input type="text" class="form-control" id="editQuoteNumber" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="editQuoteAmount" class="form-label">Montant TTC (€)</label>
                        <input type="number" class="form-control" id="editQuoteAmount" name="amount" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="editQuoteStatus" class="form-label">Statut</label>
                        <select class="form-control" id="editQuoteStatus" name="status">
                            <option value="En attente">En attente</option>
                            <option value="Accepté">Accepté</option>
                            <option value="Refusé">Refusé</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditQuote" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewQuote(btn) {
    const q = JSON.parse(btn.getAttribute('data-quote'));
    alert(
        'Devis ' + q.quote_number +
        '\nSenior: ' + q.first_name + ' ' + q.last_name +
        '\nPrestation: ' + (q.prestation_name || 'Non définie') +
        '\nMontant: ' + parseFloat(q.amount).toFixed(2) + ' €' +
        '\nStatut: ' + q.status
    );
}

function editQuote(btn) {
    const q = JSON.parse(btn.getAttribute('data-quote'));
    document.getElementById('editQuoteId').value = q.id_quote;
    document.getElementById('editQuoteNumber').value = q.quote_number;
    document.getElementById('editQuoteAmount').value = q.amount;
    document.getElementById('editQuoteStatus').value = q.status || 'En attente';
    openModal('modalEditQuote');
}
</script>

<?php
include './include/footer-admin.php';
?>
