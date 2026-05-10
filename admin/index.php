<?php
session_start();
require_once __DIR__ . '/../db.php';
include './include/header-admin.php';

$token = $_SESSION['user']['token'];

$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'user_added') {
        $message = 'Utilisateur ajouté avec succès ! Les statistiques ont été mises à jour.';
        $messageType = 'success';
    }
}

$opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Token: " . $token . "\r\n",
            "ignore_errors" => true
        ]
    ];

$context = stream_context_create($opts);
$response_usercount = file_get_contents("http://localhost:8080/api/users/active-count", false, $context);
$response_prestations = file_get_contents("http://localhost:8080/api/service-completed/count", false, $context);
$response_devis = file_get_contents("http://localhost:8080/api/quotes/count", false, $context);
$response_problemes = file_get_contents("http://localhost:8080/api/notifications/probleme/count", false, $context);
$response_transactions = file_get_contents("http://localhost:8080/api/transactions/last", false, $context);
$response_events = file_get_contents("http://localhost:8080/api/events", false, $context);
$response_pending_providers = file_get_contents("http://localhost:8080/api/transactions/pending-providers", false, $context);
$response_pending_requests = file_get_contents("http://localhost:8080/api/service-requests/pending", false, $context);

if ($response_usercount !== false) {
    $data = json_decode($response_usercount, true);
    if (isset($data['count'])) {
        $usercount = $data["count"];
    }
}

if ($response_prestations !== false) {
    $data = json_decode($response_prestations, true);
    if (isset($data['count'])) {
        $prestations_count = $data["count"];
    }
}

if ($response_devis !== false) {
    $data = json_decode($response_devis, true);
    if (isset($data['count'])) {
        $devis_count = $data["count"];
    }
}

if ($response_problemes !== false) {
    $data = json_decode($response_problemes, true);
    if (isset($data['count'])) {
        $problemes_count = $data["count"];
    }
}

$transactions = [];
$events = [];
$pending_providers = 0;
$pending_requests = 0;

if ($response_transactions !== false) {
    $data = json_decode($response_transactions, true);
    if (is_array($data)) {
        $transactions = $data;
    }
}

if ($response_events !== false) {
    $data = json_decode($response_events, true);
    if (is_array($data)) {
        $today = date('Y-m-d');
        $events = array_filter($data, function($event) use ($today) {
            return isset($event['start_date']) && $event['start_date'] >= $today;
        });
        $events = array_slice(array_values($events), 0, 5);
    }
}

if ($response_pending_providers !== false) {
    $data = json_decode($response_pending_providers, true);
    if (isset($data['count'])) {
        $pending_providers = $data["count"];
    }
}

if ($response_pending_requests !== false) {
    $data = json_decode($response_pending_requests, true);
    if (isset($data['count'])) {
        $pending_requests = $data["count"];
    }
}

$stats = [
    'users_actifs' => $usercount ?? 0,
    'prestations' => $prestations_count ?? 0,
    'devis' => $devis_count ?? 0,
    'problemes' => $problemes_count ?? 0
];
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Tableau de bord</div>

<div class="row mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-label">Utilisateurs actifs</div>
            <div class="stat-value"><?= (int)$stats['users_actifs'] ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-label">Prestations réalisées</div>
            <div class="stat-value"><?= (int)$stats['prestations'] ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-label">Devis en attente</div>
            <div class="stat-value"><?= (int)$stats['devis'] ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card danger">
            <div class="stat-label">Problèmes signalés</div>
            <div class="stat-value"><?= (int)$stats['problemes'] ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Activité du mois</h3>
            <div style="height: 300px; background: linear-gradient(180deg, rgba(40, 90, 255, 0.1) 0%, rgba(79, 70, 229, 0.05) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <p class="text-muted">Graphique d'activité (données en dur)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Dernières transactions</h3>
            <div class="list-group list-group-flush">
                <?php if (empty($transactions)): ?>
                    <p class="text-muted text-center py-3">Aucune transaction récente.</p>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <div class="list-group-item border-0 px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($tx['invoice_type']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($tx['first_name'] . ' ' . $tx['last_name']) ?> - <?= date('d/m/Y', strtotime($tx['issue_date'])) ?></small>
                                </div>
                                <span class="badge bg-success">+<?= number_format((float)$tx['amount_incl_tax'], 2) ?>€</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Événements à venir</h3>
            <div class="list-group list-group-flush">
                <?php if (empty($events)): ?>
                    <p class="text-muted text-center py-3">Aucun événement à venir.</p>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="list-group-item px-0 py-3 border-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($event['title']) ?></div>
                                    <small class="text-muted"><i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></small>
                                </div>
                                <span class="badge bg-info"><?= htmlspecialchars($event['event_type']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Tâches urgentes</h3>
            <div class="list-group list-group-flush">
                <?php if ($pending_providers > 0): ?>
                    <div class="list-group-item px-0 py-3 border-0">
                        <div class="d-flex gap-3">
                            <i class="bi bi-exclamation-circle text-danger"></i>
                            <label class="flex-grow-1"><a href="gestion-utilisateurs/prestataires.php" class="text-decoration-none text-dark">Valider <?= $pending_providers ?> demande(s) de prestataires en attente</a></label>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($pending_requests > 0): ?>
                    <div class="list-group-item px-0 py-3 border-0">
                        <div class="d-flex gap-3">
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            <label class="flex-grow-1"><a href="gestion-prestations/index.php" class="text-decoration-none text-dark">Gérer <?= $pending_requests ?> demande(s) de prestation en attente</a></label>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($stats['devis'] > 0): ?>
                    <div class="list-group-item px-0 py-3 border-0">
                        <div class="d-flex gap-3">
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            <label class="flex-grow-1"><a href="gestion-devis.php" class="text-decoration-none text-dark">Traiter <?= $stats['devis'] ?> devis en attente</a></label>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($pending_providers == 0 && $pending_requests == 0 && $stats['devis'] == 0): ?>
                    <p class="text-muted text-center py-3">Aucune tâche urgente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
