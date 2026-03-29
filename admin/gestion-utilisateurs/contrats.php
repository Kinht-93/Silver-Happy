<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$contrats = [];
$users = [];

function callAPI($url, $method = 'GET', $data = null, $token = '') {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "X-Token: {$token}\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Impossible de se connecter à l\'API'];
    }
    
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'id_user' => $_POST['id_user'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'amount' => (float)$_POST['amount'],
            'payment_method' => $_POST['payment_method'],
            'auto_renew' => isset($_POST['auto_renew']) ? true : false
        ];
        
        $response = callAPI('http://localhost:8080/api/contracts', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Contrat créé avec succès.";
            $messageType = "success";
        } else {
            $message = $response['error'] ?? "Erreur lors de la création du contrat.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'amount' => (float)$_POST['amount'],
            'payment_method' => $_POST['payment_method'],
            'status' => $_POST['status'],
            'auto_renew' => isset($_POST['auto_renew']) ? true : false
        ];
        
        $response = callAPI("http://localhost:8080/api/contracts/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Contrat modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification du contrat.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/contracts/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Contrat supprimé.";
        $messageType = "success";
    }
}

$filter = $_GET['filter'] ?? 'tous';

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/contracts', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $contrats = [];
    } elseif (is_array($response)) {
        $contrats = $response;
        
        if ($filter === 'actifs') {
            $contrats = array_filter($contrats, fn($c) => $c['status'] === 'Actif');
        } elseif ($filter === 'expires') {
            $contrats = array_filter($contrats, fn($c) => $c['jours_restants'] <= 30 && $c['jours_restants'] > 0 && $c['status'] === 'Actif');
        } elseif ($filter === 'expired') {
            $contrats = array_filter($contrats, fn($c) => $c['jours_restants'] < 0);
        }
    }

    $usersResponse = callAPI('http://localhost:8080/api/users-without-contract', 'GET', null, $token);
    if (is_array($usersResponse) && !isset($usersResponse['error'])) {
        $users = $usersResponse;
    }
} else {
    $message = "Token d'authentification manquant.";
    $messageType = "danger";
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des Contrats</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="?filter=tous" class="btn btn-sm <?= ($filter === 'tous' ? 'btn-primary' : 'btn-outline-primary') ?>">Tous</a>
            <a href="?filter=actifs" class="btn btn-sm <?= ($filter === 'actifs' ? 'btn-primary' : 'btn-outline-primary') ?>">Actifs</a>
            <a href="?filter=expires" class="btn btn-sm <?= ($filter === 'expires' ? 'btn-primary' : 'btn-outline-primary') ?>">Expirés bientôt</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddContract">+ Ajouter un contrat</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Durée restante</th>
                    <th>Montant</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contrats)): ?>
                    <tr><td colspan="9" class="text-center">Aucun contrat trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($contrats as $contrat): ?>
                        <?php $alert = $contrat['jours_restants'] <= 30 && $contrat['jours_restants'] > 0 ? ' style="background-color: #fff3cd;"' : ''; ?>
                        <tr<?= $alert ?>>
                            <td><?= htmlspecialchars($contrat['first_name'] . ' ' . $contrat['last_name']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($contrat['role']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($contrat['start_date'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($contrat['end_date'])) ?></td>
                            <td>
                                <?php if ($contrat['jours_restants'] < 0): ?>
                                    <span class="badge bg-danger">Expiré</span>
                                <?php elseif ($contrat['jours_restants'] <= 30): ?>
                                    <span class="badge bg-warning"><?= $contrat['jours_restants'] ?> jours</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $contrat['jours_restants'] ?> jours</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((float)$contrat['amount'], 2) ?>€</td>
                            <td><?= htmlspecialchars($contrat['payment_method']) ?></td>
                            <td><span class="badge bg-<?= $contrat['status'] === 'Actif' ? 'success' : 'danger' ?>"><?= htmlspecialchars($contrat['status']) ?></span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editContract(<?= htmlspecialchars(json_encode($contrat)) ?>)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($contrat['id_contract']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAddContract" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un contrat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddContract">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="addUser" class="form-label">Utilisateur *</label>
                        <select class="form-control" id="addUser" name="id_user" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['id_user']) ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['role'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addStartDate" class="form-label">Date début *</label>
                        <input type="date" class="form-control" id="addStartDate" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="addEndDate" class="form-label">Date fin *</label>
                        <input type="date" class="form-control" id="addEndDate" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="addAmount" class="form-label">Montant *</label>
                        <input type="number" step="0.01" class="form-control" id="addAmount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPaymentMethod" class="form-label">Méthode paiement *</label>
                        <select class="form-control" id="addPaymentMethod" name="payment_method" required>
                            <option value="Virement">Virement</option>
                            <option value="Chèque">Chèque</option>
                            <option value="Prélèvement">Prélèvement</option>
                            <option value="Carte">Carte bancaire</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="addAutoRenew" name="auto_renew">
                        <label class="form-check-label" for="addAutoRenew">Renouvellement automatique</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditContract" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier contrat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditContract">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editContractId">
                    <div class="mb-3">
                        <label for="editStartDate" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="editStartDate" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEndDate" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="editEndDate" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAmount" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control" id="editAmount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPaymentMethod" class="form-label">Méthode paiement</label>
                        <select class="form-control" id="editPaymentMethod" name="payment_method" required>
                            <option value="Virement">Virement</option>
                            <option value="Chèque">Chèque</option>
                            <option value="Prélèvement">Prélèvement</option>
                            <option value="Carte">Carte bancaire</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Statut</label>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="Actif">Actif</option>
                            <option value="Expiré">Expiré</option>
                            <option value="Résilié">Résilié</option>
                            <option value="Suspendu">Suspendu</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="editAutoRenew" name="auto_renew">
                        <label class="form-check-label" for="editAutoRenew">Renouvellement automatique</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editContract(contract) {
    document.getElementById('editContractId').value = contract.id_contract;
    document.getElementById('editStartDate').value = contract.start_date;
    document.getElementById('editEndDate').value = contract.end_date;
    document.getElementById('editAmount').value = contract.amount;
    document.getElementById('editPaymentMethod').value = contract.payment_method;
    document.getElementById('editStatus').value = contract.status;
    document.getElementById('editAutoRenew').checked = contract.auto_renew === true || contract.auto_renew === 1;
    new bootstrap.Modal(document.getElementById('modalEditContract')).show();
}
</script>

<?php
include '../include/footer-admin.php';
?>