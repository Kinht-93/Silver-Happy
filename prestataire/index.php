<?php
include_once __DIR__ . '/_auth.php';
$basePath = '../';
include '../include/header.php';

$totalDisponibilites = 0;
$totalMissionsAcceptees = 0;
$totalFactures = 0;

if ($providerData && $token !== '') {
    $dashboardResponse = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-dashboard', 'GET', null, $token);
    if (is_array($dashboardResponse) && !isset($dashboardResponse['error'])) {
        $totalDisponibilites = (int)($dashboardResponse['availabilities_count'] ?? 0);
        $totalMissionsAcceptees = (int)($dashboardResponse['accepted_missions_count'] ?? 0);
        $totalFactures = (int)($dashboardResponse['invoices_count'] ?? 0);
    } else {
        $dbPageError = 'Erreur API: ' . (string)($dashboardResponse['error'] ?? 'Impossible de charger les statistiques prestataire.');
    }
}
?>

<?php if ($dbPageError): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($dbPageError) ?></div>
<?php endif; ?>

<div class="page-title h3 mb-3">Espace prestataire</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if (!$providerData): ?>
<div class="alert alert-warning" role="alert">
    Votre compte prestataire n'est pas encore relie a une fiche fournisseur. Contactez un administrateur.
</div>
<?php else: ?>
    <?php if (!$isProviderValidated): ?>
    <div class="alert alert-warning" role="alert">
        Compte en attente de validation administrateur. Vous pouvez completer votre profil, mais certaines actions restent limitees.
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="small text-muted">Disponibilites</div><div class="h4 mb-0"><?= $totalDisponibilites ?></div></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="small text-muted">Missions acceptees</div><div class="h4 mb-0"><?= $totalMissionsAcceptees ?></div></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="small text-muted">Factures generees</div><div class="h4 mb-0"><?= $totalFactures ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Resume</h5>
            <p class="mb-1"><strong>Entreprise:</strong> <?= htmlspecialchars($providerData['company_name'] ?? 'N/A') ?></p>
            <p class="mb-1"><strong>SIRET:</strong> <?= htmlspecialchars($providerData['siret_number'] ?? 'N/A') ?></p>
            <p class="mb-0"><strong>Validation:</strong> <?= htmlspecialchars((string)($providerData['validation_status'] ?? 'Inconnu')) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>
