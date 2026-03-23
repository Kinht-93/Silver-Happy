<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO && $providerData) {
    $action = $_POST['action'] ?? '';
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour generer des factures.');
        }

        if ($action === 'generate') {
            $monthLabel = date('Y-m');
            $invoiceId = uniqid('pinv_');

            $countStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM provider_missions
                                 WHERE id_user = ?
                   AND status = 'Acceptee'
                   AND DATE_FORMAT(COALESCE(accepted_at, created_at), '%Y-%m') = ?"
            );
                        $countStmt->execute([$providerData['id_user'], $monthLabel]);
            $missionsCount = (int)$countStmt->fetchColumn();

            $amount = $missionsCount * 25.00;

            $insertInvoice = $pdo->prepare(
                "INSERT INTO provider_invoices (id_invoice, id_user, month_label, amount, status, generated_at)
                 VALUES (?, ?, ?, ?, 'Generee', NOW())"
            );
            $insertInvoice->execute([$invoiceId, $providerData['id_user'], $monthLabel, $amount]);

            $insertPayment = $pdo->prepare(
                "INSERT INTO provider_payments (id_payment, id_invoice, id_user, amount, paid_at, status)
                 VALUES (?, ?, ?, ?, NULL, 'En attente')"
            );
            $insertPayment->execute([uniqid('ppay_'), $invoiceId, $providerData['id_user'], $amount]);

            $message = 'Facture mensuelle generee.';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            $message = 'Facture deja generee pour ce mois.';
            $messageType = 'warning';
        } else {
            $message = 'Erreur: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$rows = [];
if ($pdo instanceof PDO && $providerData) {
    try {
        $stmt = $pdo->prepare(
            "SELECT i.id_invoice, i.month_label, i.amount, i.status AS invoice_status, i.generated_at,
                    p.status AS payment_status, p.paid_at
             FROM provider_invoices i
             LEFT JOIN provider_payments p ON p.id_invoice = i.id_invoice
               WHERE i.id_user = ?
             ORDER BY i.generated_at DESC"
        );
           $stmt->execute([$providerData['id_user']]);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$basePath = '../';
include '../include/header.php';
?>

<div class="page-title h3 mb-3">Facturation et paiements</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>" role="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!$providerData): ?>
<div class="alert alert-warning" role="alert">Aucune fiche prestataire associee.</div>
<?php else: ?>
    <?php if (!$isProviderValidated): ?>
    <div class="alert alert-warning" role="alert">Compte non valide: generation de facture bloquee.</div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Generer la facture du mois</h5>
                <div class="text-muted small">Mois courant: <?= htmlspecialchars(date('Y-m')) ?></div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="generate">
                <button class="btn btn-primary" <?= !$isProviderValidated ? 'disabled' : '' ?>>Generer</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Suivi des paiements</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Mois</th><th>Montant</th><th>Facture</th><th>Paiement</th><th>Date paiement</th></tr></thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center">Aucune facture.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['month_label']) ?></td>
                            <td><?= number_format((float)$r['amount'], 2) ?> EUR</td>
                            <td><?= htmlspecialchars((string)$r['invoice_status']) ?></td>
                            <td><?= htmlspecialchars((string)($r['payment_status'] ?? 'En attente')) ?></td>
                            <td><?= htmlspecialchars((string)($r['paid_at'] ?? '-')) ?></td>
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
