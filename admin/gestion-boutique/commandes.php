<?php
include '../include/header-admin.php';

$query = "
    SELECT o.id_order, o.order_number, o.amount, o.order_date, o.delivery_method, o.status,
           u.first_name, u.last_name,
           (SELECT GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ' + ') 
            FROM order_items oi 
            JOIN products p ON oi.id_product = p.id_product 
            WHERE oi.id_order = o.id_order) as items
    FROM orders o
    JOIN users u ON o.id_user = u.id_user
    ORDER BY o.order_date DESC
";
$commandes = $pdo->query($query)->fetchAll();

$total_commandes = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$en_attente = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'En attente'")->fetchColumn();
$livrees = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Livrée'")->fetchColumn();
$retours = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Retour demandé'")->fetchColumn();
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Commandes</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Articles</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./commandes.php" class="btn btn-sm btn-primary active">Commandes</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Commandes totales</div>
            <div class="stat-value"><?= (int)$total_commandes ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">En attente</div>
            <div class="stat-value"><?= (int)$en_attente ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Livrées</div>
            <div class="stat-value"><?= (int)$livrees ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">Retours</div>
            <div class="stat-value"><?= (int)$retours ?></div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Article(s)</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Livraison</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($commandes)): ?>
                    <tr><td colspan="8" class="text-center">Aucune commande trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($commande['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($commande['first_name'] . ' ' . $commande['last_name']) ?></td>
                            <td><?= htmlspecialchars($commande['items']) ?: 'Aucun article' ?></td>
                            <td><?= number_format($commande['amount'], 2) ?>€</td>
                            <td><?= date('d/m/Y', strtotime($commande['order_date'])) ?></td>
                            <td><?= htmlspecialchars($commande['delivery_method']) ?></td>
                            <td>
                                <?php if ($commande['status'] == 'Livrée'): ?>
                                    <span class="badge bg-success">Livrée</span>
                                <?php elseif ($commande['status'] == 'Retour demandé'): ?>
                                    <span class="badge bg-danger">Retour demandé</span>
                                <?php elseif ($commande['status'] == 'En cours de livraison'): ?>
                                    <span class="badge bg-info">En cours de livraison</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= htmlspecialchars($commande['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-order="<?= htmlspecialchars(json_encode($commande)) ?>"
                                    onclick="viewOrder(this)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-order="<?= htmlspecialchars(json_encode($commande)) ?>"
                                    onclick="editOrder(this)"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalViewOrder" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la commande</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <p><strong>N° Commande:</strong> <span id="viewOrderNumber"></span></p>
                <p><strong>Client:</strong> <span id="viewOrderClient"></span></p>
                <p><strong>Montant:</strong> <span id="viewOrderAmount"></span></p>
                <p><strong>Date:</strong> <span id="viewOrderDate"></span></p>
                <p><strong>Livraison:</strong> <span id="viewOrderDelivery"></span></p>
                <p><strong>Statut:</strong> <span id="viewOrderStatus"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditOrder" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le statut de la commande</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form id="formEditOrder" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="editOrderId" name="id">
                    <div class="mb-3">
                        <label for="editOrderStatus" class="form-label">Statut *</label>
                        <select class="form-control" id="editOrderStatus" name="status" required>
                            <option value="">Sélectionner un statut</option>
                            <option value="En attente">En attente</option>
                            <option value="En cours de livraison">En cours de livraison</option>
                            <option value="Livrée">Livrée</option>
                            <option value="Retour demandé">Retour demandé</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(btn) {
    const order = JSON.parse(btn.getAttribute('data-order'));
    document.getElementById('viewOrderNumber').textContent = order.order_number;
    document.getElementById('viewOrderClient').textContent = order.first_name + ' ' + order.last_name;
    document.getElementById('viewOrderAmount').textContent = parseFloat(order.amount).toFixed(2) + '€';
    document.getElementById('viewOrderDate').textContent = new Date(order.order_date).toLocaleDateString('fr-FR');
    document.getElementById('viewOrderDelivery').textContent = order.delivery_method;
    document.getElementById('viewOrderStatus').textContent = order.status;
    openModal('modalViewOrder');
}

function editOrder(btn) {
    const order = JSON.parse(btn.getAttribute('data-order'));
    document.getElementById('editOrderId').value = order.id_order;
    document.getElementById('editOrderStatus').value = order.status || '';
    openModal('modalEditOrder');
}
</script>

<?php
include '../include/footer-admin.php';
?>