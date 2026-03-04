<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            if (empty($_POST['id_user']) || empty($_POST['id_service_type']) || empty($_POST['amount'])) {
                throw new InvalidArgumentException('Merci de renseigner le senior, la prestation et le montant.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id_service_category FROM service_types WHERE id_service_type = ?");
            $stmt->execute([$_POST['id_service_type']]);
            $serviceType = $stmt->fetch();
            if (!$serviceType) {
                throw new RuntimeException("Type de prestation introuvable.");
            }

            $id_request = uniqid('req_');

            $stmt = $pdo->prepare("
                INSERT INTO service_requests (
                    id_request, desired_date, start_time, estimated_duration,
                    intervention_address, status, created_at, id_user, id_service_category
                ) VALUES (?, CURDATE(), '09:00:00', 1, ?, 'En attente', NOW(), ?, ?)
            ");
            $stmt->execute([
                $id_request,
                'Adresse à définir',
                $_POST['id_user'],
                $serviceType['id_service_category']
            ]);

            $year = date('Y');
            $lastNumber = $pdo->prepare("SELECT quote_number FROM quotes WHERE quote_number LIKE ? ORDER BY quote_number DESC LIMIT 1");
            $like = "DV-$year-%";
            $lastNumber->execute([$like]);
            $last = $lastNumber->fetchColumn();
            $seq = 1;
            if ($last && preg_match('#DV-' . $year . '-(\d{3})#', $last, $m)) {
                $seq = (int)$m[1] + 1;
            }
            $quoteNumber = sprintf('DV-%s-%03d', $year, $seq);

            $id_quote = uniqid('quo_');
            $amountIncl = (float)$_POST['amount'];
            $taxRate = 20.0;
            $amountExcl = $amountIncl > 0 ? round($amountIncl / (1 + $taxRate / 100), 2) : 0;

            $stmt = $pdo->prepare("
                INSERT INTO quotes (
                    id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax,
                    status, created_at, id_request
                ) VALUES (?, ?, ?, ?, ?, 'En attente', NOW(), ?)
            ");
            $stmt->execute([
                $id_quote,
                $quoteNumber,
                $amountExcl,
                $taxRate,
                $amountIncl,
                $id_request
            ]);

            $pdo->commit();
            $message = "Devis créé avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            if (empty($_POST['id'])) {
                throw new InvalidArgumentException('Identifiant devis manquant.');
            }
            $amountIncl = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
            $status = $_POST['status'] ?? 'En attente';
            $taxRate = 20.0;
            $amountExcl = $amountIncl > 0 ? round($amountIncl / (1 + $taxRate / 100), 2) : 0;

            $stmt = $pdo->prepare("
                UPDATE quotes
                SET amount_excl_tax = ?, tax_rate = ?, amount_incl_tax = ?, status = ?
                WHERE id_quote = ?
            ");
            $stmt->execute([
                $amountExcl,
                $taxRate,
                $amountIncl,
                $status,
                $_POST['id']
            ]);

            $message = "Devis mis à jour.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            if (empty($_POST['id'])) {
                throw new InvalidArgumentException('Identifiant devis manquant.');
            }
            $stmt = $pdo->prepare("DELETE FROM quotes WHERE id_quote = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Devis supprimé.";
            $messageType = "success";
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur sur la gestion du devis: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT q.id_quote, q.quote_number, q.amount_incl_tax as amount, q.created_at, q.status,
           u.first_name, u.last_name,
           st.name as prestation_name
    FROM quotes q
    JOIN service_requests sr ON q.id_request = sr.id_request
    JOIN users u ON sr.id_user = u.id_user
    LEFT JOIN show_type sht ON sr.id_request = sht.id_request
    LEFT JOIN service_types st ON sht.id_service_type = st.id_service_type
    ORDER BY q.created_at DESC
";
$devis = $pdo->query($query)->fetchAll();

$seniors = $pdo->query("
    SELECT id_user, first_name, last_name 
    FROM users 
    WHERE LOWER(role) = 'senior'
    ORDER BY last_name, first_name
")->fetchAll();

$serviceTypes = $pdo->query("
    SELECT id_service_type, name 
    FROM service_types
    ORDER BY name
")->fetchAll();
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
