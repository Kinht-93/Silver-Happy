<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providerData && $token !== '') {
    $action = $_POST['action'] ?? '';
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour generer des factures.');
        }

        if ($action === 'generate') {
            $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-invoices/generate', 'POST', null, $token);
            if (!is_array($response) || isset($response['error'])) {
                throw new RuntimeException((string)($response['error'] ?? 'Impossible de generer la facture.'));
            }

            $message = 'Facture mensuelle generee.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = strpos($message, 'deja generee') !== false ? 'warning' : 'danger';
    }
}

$rows = [];
if ($providerData && $token !== '') {
    $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-billing', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $rows = $response;
    } else {
        $message = 'Erreur: ' . (string)($response['error'] ?? 'Impossible de charger la facturation.');
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
