<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_status') {
            $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id_invoice = ?");
            $stmt->execute([
                $_POST['status'] ?? 'En attente',
                $_POST['id'] ?? ''
            ]);
            $message = "Statut de la facture mis à jour.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$queryFactures = "
    SELECT 
        i.id_invoice,
        i.issue_date,
        i.amount_incl_tax AS amount,
        i.status,
        u.first_name,
        u.last_name
    FROM invoices i
    JOIN quotes q ON i.id_quote = q.id_quote
    JOIN service_requests sr ON q.id_request = sr.id_request
    JOIN users u ON sr.id_user = u.id_user
    ORDER BY i.issue_date DESC
    LIMIT 20
";
try {
    $factures = $pdo ? $pdo->query($queryFactures)->fetchAll() : [];
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
    $messageType = "danger";
    $factures = [];
}

$ca = $pdo->query("SELECT SUM(amount_incl_tax) FROM invoices WHERE status = 'Payée'")->fetchColumn();
$payees = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'Payée'")->fetchColumn();
$attente = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'En attente'")->fetchColumn();
$retard = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'En retard'")->fetchColumn();
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Facturation & paiements</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Chiffre d'affaires</div>
            <div class="stat-value"><?= number_format((float)$ca, 2) ?>€</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Paiements reçus</div>
            <div class="stat-value"><?= (int)$payees ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">En attente</div>
            <div class="stat-value"><?= (int)$attente ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">En retard</div>
            <div class="stat-value"><?= (int)$retard ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card p-4">
            <h5 class="mb-3">Factures récentes</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($factures)): ?>
                            <tr><td colspan="7" class="text-center">Aucune facture trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($factures as $facture): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars(substr($facture['id_invoice'], 0, 13)) ?></strong></td>
                                    <td><?= htmlspecialchars($facture['first_name'] . ' ' . $facture['last_name']) ?></td>
                                    <td><?= number_format($facture['amount'], 2) ?>€</td>
                                    <td><?= date('d/m/Y', strtotime($facture['issue_date'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($facture['issue_date'] . ' + 30 days')) ?></td>
                                    <td>
                                        <?php if ($facture['status'] == 'Payée'): ?>
                                            <span class="badge bg-success">Payée</span>
                                        <?php elseif ($facture['status'] == 'En attente'): ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?= htmlspecialchars($facture['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-invoice="<?= htmlspecialchars(json_encode($facture)) ?>"
                                            onclick="editInvoice(this)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="downloadInvoice('<?= htmlspecialchars($facture['id_invoice']) ?>')"
                                        >
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h5 class="mb-3">Moyens de paiement acceptés</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-credit-card"></i> Carte bancaire</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-paypal"></i> PayPal</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bank"></i> Virement bancaire</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-phone"></i> Paiement au tél</span>
                    <input type="checkbox" class="form-check-input">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditInvoice" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le statut de la facture</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form id="formEditInvoice" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="editInvoiceId" name="id">
                    <div class="mb-3">
                        <label for="editInvoiceStatus" class="form-label">Statut *</label>
                        <select class="form-control" id="editInvoiceStatus" name="status" required>
                            <option value="">Sélectionner un statut</option>
                            <option value="En attente">En attente</option>
                            <option value="Payée">Payée</option>
                            <option value="En retard">En retard</option>
                            <option value="Annulée">Annulée</option>
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
function editInvoice(btn) {
    const invoice = JSON.parse(btn.getAttribute('data-invoice'));
    document.getElementById('editInvoiceId').value = invoice.id_invoice;
    document.getElementById('editInvoiceStatus').value = invoice.status || '';
    openModal('modalEditInvoice');
}

function downloadInvoice(invoiceId) {
    showToast('Téléchargement de la facture en cours...', 'info');
}
</script>

<?php
include './include/footer-admin.php';
?>
