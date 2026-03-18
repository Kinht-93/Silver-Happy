<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_types (id_subscription_type, name, user_type, monthly_price, yearly_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $monthly = isset($_POST['monthly_price']) ? (float)$_POST['monthly_price'] : 0;
            $yearly = $monthly > 0 ? $monthly * 12 : 0;
            $stmt->execute([
                uniqid('sub_'),
                $_POST['name'],
                $_POST['user_type'],
                $monthly,
                $yearly
            ]);
            $message = "Nouvelle formule d'abonnement créée avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $monthly = isset($_POST['monthly_price']) ? (float)$_POST['monthly_price'] : 0;
            $yearly = $monthly > 0 ? $monthly * 12 : 0;
            $stmt = $pdo->prepare("
                UPDATE subscription_types
                SET name = ?, user_type = ?, monthly_price = ?, yearly_price = ?
                WHERE id_subscription_type = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['user_type'],
                $monthly,
                $yearly,
                $_POST['id']
            ]);
            $message = "Formule d'abonnement mise à jour.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM subscription_types WHERE id_subscription_type = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Formule d'abonnement supprimée avec succès.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT st.id_subscription_type, st.name, st.user_type, st.monthly_price,
           (SELECT COUNT(*) FROM subscribed s WHERE s.id_subscription_type = st.id_subscription_type) as abonnes
    FROM subscription_types st
    ORDER BY st.monthly_price ASC
";
try {
    $subscriptions = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors du chargement des abonnements: " . $e->getMessage();
    $messageType = "danger";
    $subscriptions = [];
}

try {
    $total_actifs = $pdo->query("SELECT COUNT(DISTINCT id_user) FROM subscribed")->fetchColumn();
} catch (PDOException $e) {
    $total_actifs = 0;
}

$query_revenus = "
    SELECT SUM(st.monthly_price)
    FROM subscribed s
    JOIN subscription_types st ON s.id_subscription_type = st.id_subscription_type
";
try {
    $revenus = $pdo->query($query_revenus)->fetchColumn();
} catch (PDOException $e) {
    $revenus = 0;
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des abonnements</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <button class="btn btn-sm btn-primary active" id="tous-tab" data-bs-toggle="tab" data-bs-target="#tous" type="button" role="tab">Tous</button>
            <button class="btn btn-sm btn-outline-primary" id="actifs-tab" data-bs-toggle="tab" data-bs-target="#actifs" type="button" role="tab">Actifs</button>
            <button class="btn btn-sm btn-outline-primary" id="suspendus-tab" data-bs-toggle="tab" data-bs-target="#suspendus" type="button" role="tab">Suspendus</button>
            <button class="btn btn-sm btn-outline-primary" id="resilies-tab" data-bs-toggle="tab" data-bs-target="#resilies" type="button" role="tab">Résiliés</button>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddSubscription" type="button">+ Nouvelle formule</button>
    </div>

</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tous" role="tabpanel">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card primary">
                    <div class="stat-label">Abonnements actifs</div>
                    <div class="stat-value"><?= (int)$total_actifs ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card warning">
                    <div class="stat-label">Suspendus</div>
                    <div class="stat-value">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card success">
                    <div class="stat-label">Revenus mensuels</div>
                    <div class="stat-value"><?= number_format((float)$revenus, 2) ?>€</div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th>Acronyme</th>
                            <th>Prix/mois</th>
                            <th>Avantages</th>
                            <th>Abonnés</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscriptions)): ?>
                            <tr><td colspan="7" class="text-center">Aucun abonnement trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sub['name']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark"><?= strtoupper(substr($sub['name'], 0, 4)) ?></span></td>
                                    <td><?= $sub['monthly_price'] == 0 ? 'Gratuit' : number_format($sub['monthly_price'], 2) . '€' ?></td>
                                    <td>
                                        <small>
                                            <i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Avantages inclus<br>
                                            <i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Support <?= htmlspecialchars($sub['user_type']) ?>
                                        </small>
                                    </td>
                                    <td><?= (int)$sub['abonnes'] ?></td>
                                    <td><span class="badge bg-success">Actif</span></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-secondary"
                                            type="button"
                                            data-sub="<?= htmlspecialchars(json_encode($sub)) ?>"
                                            onclick="editSubscription(this)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette formule ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($sub['id_subscription_type']) ?>">
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
    </div>

    <div class="tab-pane fade" id="actifs" role="tabpanel">
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th>Acronyme</th>
                            <th>Prix/mois</th>
                            <th>Avantages</th>
                            <th>Abonnés</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscriptions)): ?>
                            <tr><td colspan="7" class="text-center">Aucun abonnement trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sub['name']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark"><?= strtoupper(substr($sub['name'], 0, 4)) ?></span></td>
                                    <td><?= $sub['monthly_price'] == 0 ? 'Gratuit' : number_format($sub['monthly_price'], 2) . '€' ?></td>
                                    <td>
                                        <small>
                                            <i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Avantages inclus<br>
                                            <i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Support <?= htmlspecialchars($sub['user_type']) ?>
                                        </small>
                                    </td>
                                    <td><?= (int)$sub['abonnes'] ?></td>
                                    <td><span class="badge bg-success">Actif</span></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-secondary"
                                            type="button"
                                            data-sub="<?= htmlspecialchars(json_encode($sub)) ?>"
                                            onclick="editSubscription(this)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette formule ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($sub['id_subscription_type']) ?>">
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
    </div>

    <div class="tab-pane fade" id="suspendus" role="tabpanel">
        <div class="admin-card">
            <p class="text-muted">Aucun abonnement suspendu pour le moment.</p>
        </div>
    </div>

    <div class="tab-pane fade" id="resilies" role="tabpanel">
        <div class="admin-card">
            <p class="text-muted">Aucun abonnement résilié pour le moment.</p>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddSubscription" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle formule d'abonnement</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddSubscription">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="subscriptionName" class="form-label">Nom de la formule *</label>
                        <input type="text" class="form-control" id="subscriptionName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="subscriptionUserType" class="form-label">Type d'utilisateur concerné *</label>
                        <select class="form-control" id="subscriptionUserType" name="user_type" required>
                            <option value="">Sélectionner un type</option>
                            <option value="senior">Senior</option>
                            <option value="prestataire">Prestataire</option>
                            <option value="employe">Employé</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subscriptionPrice" class="form-label">Prix mensuel (€)</label>
                        <input type="number" class="form-control" id="subscriptionPrice" name="monthly_price" step="0.01" min="0" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddSubscription" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditSubscription" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la formule d'abonnement</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditSubscription">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editSubscriptionId" name="id">
                    <div class="mb-3">
                        <label for="editSubscriptionName" class="form-label">Nom de la formule *</label>
                        <input type="text" class="form-control" id="editSubscriptionName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriptionUserType" class="form-label">Type d'utilisateur concerné *</label>
                        <select class="form-control" id="editSubscriptionUserType" name="user_type" required>
                            <option value="senior">Senior</option>
                            <option value="prestataire">Prestataire</option>
                            <option value="employe">Employé</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriptionPrice" class="form-label">Prix mensuel (€)</label>
                        <input type="number" class="form-control" id="editSubscriptionPrice" name="monthly_price" step="0.01" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditSubscription" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function editSubscription(btn) {
    const sub = JSON.parse(btn.getAttribute('data-sub'));
    document.getElementById('editSubscriptionId').value = sub.id_subscription_type;
    document.getElementById('editSubscriptionName').value = sub.name || '';
    document.getElementById('editSubscriptionUserType').value = sub.user_type || '';
    document.getElementById('editSubscriptionPrice').value = sub.monthly_price || 0;
    openModal('modalEditSubscription');
}
</script>

<?php
include './include/footer-admin.php';
?>
