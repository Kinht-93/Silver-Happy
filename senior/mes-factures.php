<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'factures';
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$invoices = [];
$loadError = '';

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $stmt = $pdo->prepare(
            "SELECT i.invoice_number, i.invoice_type, i.amount_excl_tax, i.tax_rate, i.amount_incl_tax,
                    i.issue_date, i.due_date, i.status,
                    sr.desired_date, sc.name AS category_name
             FROM invoices i
             INNER JOIN quotes q ON q.id_quote = i.id_quote
             INNER JOIN service_requests sr ON sr.id_request = q.id_request
             INNER JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
             WHERE sr.id_user = ?
             ORDER BY i.issue_date DESC, i.invoice_number DESC"
        );
        $stmt->execute([$userId]);
        $invoices = $stmt->fetchAll();
    } catch (Throwable $e) {
        $loadError = 'Impossible de charger vos factures.';
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Historique des factures</h1>
        <p class="senior-subtitle">Retrouvez ici vos factures et paiements.</p>

        <div class="account-card">
            <div class="invoice-panel">
                <?php if ($loadError): ?>
                    <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($loadError) ?></div>
                <?php endif; ?>

                <?php if (empty($invoices)): ?>
                    <div class="invoice-empty-state">
                        Aucune facture disponible pour le moment.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Prestation</th>
                                    <th>Date emission</th>
                                    <th>Echeance</th>
                                    <th>Montant TTC</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$invoice['invoice_number']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars((string)$invoice['category_name']) ?></div>
                                            <small class="text-muted">Intervention: <?= htmlspecialchars(date('d/m/Y', strtotime((string)$invoice['desired_date']))) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)$invoice['issue_date']))) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)$invoice['due_date']))) ?></td>
                                        <td><?= number_format((float)$invoice['amount_incl_tax'], 2, ',', ' ') ?> EUR</td>
                                        <td><?= htmlspecialchars((string)$invoice['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
