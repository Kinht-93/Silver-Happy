<?php
session_start();
require_once __DIR__ . '/../db.php';
include './include/header-admin.php';

$token = $_SESSION['user']['token'];


// userscount


$options = [
    "http" => [
        "method" => "GET",
        "header" => "X-Token: " . $token . "\r\n",
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);

$usercount = file_get_contents("http://localhost:8080/api/users/active-count", false, $context);
$data = json_decode($usercount, true);
$usercount = $data["count"] ?? 0;


// DEVIS /QUOTES

$options = [
    "http" => [
        "method" => "GET",
        "header" => "X-Token: " . $token . "\r\n",
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);

$servicecount = file_get_contents("http://localhost:8080/api/quotes/count", false, $context);
$data = json_decode($servicecount, true);
$servicecount = $data["count"] ?? 0;


$stats = [
    'users' => $usercount,
    'prestations' => $pdo->query("SELECT COUNT(*) FROM completed_services WHERE status = 'Terminé'")->fetchColumn(),
    'devis' => $servicecount,
    'problemes' => 0
];

$transactions = $pdo->query("SELECT i.amount_incl_tax, i.invoice_type, i.issue_date, u.first_name, u.last_name 
                             FROM invoices i 
                             JOIN quotes q ON i.id_quote = q.id_quote
                             JOIN service_requests sr ON q.id_request = sr.id_request
                             JOIN users u ON sr.id_user = u.id_user
                             ORDER BY i.issue_date DESC LIMIT 3")->fetchAll();

$events = $pdo->query("SELECT title, start_date, event_type FROM events WHERE start_date >= CURDATE() ORDER BY start_date ASC LIMIT 5")->fetchAll();

$pending_providers = $pdo->query("SELECT COUNT(*) FROM providers WHERE validation_status = 'En attente'")->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'En attente'")->fetchColumn();

?>

<div class="page-title">Tableau de bord</div>

<div class="row mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-label">Utilisateurs actifs</div>
            <div class="stat-value"><?= number_format($stats['users']) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-label">Prestations réalisées</div>
            <div class="stat-value"><?= number_format($stats['prestations']) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-label">Devis en attente</div>
            <div class="stat-value"><?= number_format($stats['devis']) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card danger">
            <div class="stat-label">Problèmes signalés</div>
            <div class="stat-value"><?= number_format($stats['problemes']) ?></div>
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
                                <span class="badge bg-success">+<?= number_format($tx['amount_incl_tax'], 2) ?>€</span>
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
