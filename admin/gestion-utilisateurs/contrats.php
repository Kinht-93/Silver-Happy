<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM contracts WHERE id_user = ? AND status = 'Actif'");
            $checkStmt->execute([$_POST['id_user']]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Cet utilisateur possède déjà un contrat actif. Un seul contrat par utilisateur autorisé.");
            }
            
            $id_contract = uniqid('ctr_');
            $stmt = $pdo->prepare("
                INSERT INTO contracts (id_contract, id_user, start_date, end_date, amount, payment_method, status, auto_renew, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $id_contract,
                $_POST['id_user'],
                $_POST['start_date'],
                $_POST['end_date'],
                (float)$_POST['amount'],
                $_POST['payment_method'],
                'Actif',
                isset($_POST['auto_renew']) ? 1 : 0
            ]);
            $message = "Contrat créé avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE contracts 
                SET start_date=?, end_date=?, amount=?, payment_method=?, status=?, auto_renew=?, updated_at=NOW()
                WHERE id_contract=?
            ");
            $stmt->execute([
                $_POST['start_date'],
                $_POST['end_date'],
                (float)$_POST['amount'],
                $_POST['payment_method'],
                $_POST['status'],
                isset($_POST['auto_renew']) ? 1 : 0,
                $_POST['id']
            ]);
            $message = "Contrat modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM contracts WHERE id_contract=?");
            $stmt->execute([$_POST['id']]);
            $message = "Contrat supprimé.";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    } catch (PDOException $e) {
        $message = "Erreur BD: " . $e->getMessage();
        $messageType = "danger";
    }
}

$filter = $_GET['filter'] ?? 'tous';
$whereClause = '';
if ($filter === 'actifs') {
    $whereClause = "WHERE c.status = 'Actif'";
} elseif ($filter === 'expires') {
    $whereClause = "WHERE c.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND c.status = 'Actif'";
} elseif ($filter === 'expired') {
    $whereClause = "WHERE c.end_date < CURDATE()";
}

$query = "
    SELECT c.id_contract, c.id_user, c.start_date, c.end_date, c.amount, c.payment_method, c.status, c.auto_renew,
           u.first_name, u.last_name, u.email, u.role,
           DATEDIFF(c.end_date, CURDATE()) as jours_restants
    FROM contracts c
    INNER JOIN users u ON c.id_user = u.id_user
    $whereClause
    ORDER BY c.end_date ASC
";
try {
    $contrats = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contrats = [];
    $message = "Erreur BD: " . $e->getMessage();
    $messageType = "danger";
}

try {
    $users = $pdo->query("
        SELECT u.id_user, u.first_name, u.last_name, u.role
        FROM users u
        WHERE u.active = TRUE
        AND NOT EXISTS (
            SELECT 1 FROM contracts c 
            WHERE c.id_user = u.id_user AND c.status = 'Actif'
        )
        ORDER BY u.last_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
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
    new bootstrap.Modal(document.getElementById('modalEditContract')).show();
}
</script>

<?php
include '../include/footer-admin.php';
?>