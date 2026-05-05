<?php
include './include/header.php';
require_once __DIR__ . '/include/callapi.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$notifications = [];
$message = '';
$messageType = '';

if ($userId === '') {
    $message = 'Utilisateur non identifié. Veuillez vous reconnecter.';
    $messageType = 'warning';
} else {
    $response = callAPI('http://localhost:8080/api/notifications?id_user=' . urlencode($userId), 'GET', null, $token);
    if (isset($response['error'])) {
        $message = 'Erreur API : ' . htmlspecialchars($response['error']);
        $messageType = 'danger';
    } elseif (!is_array($response)) {
        $message = 'Réponse inattendue de l’API.';
        $messageType = 'warning';
    } else {
        $notifications = $response;
    }
}
?>

<div class="page-title">Mes notifications</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Notifications récentes</h5>
                <?php if (empty($notifications)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-bell-slash fs-1 mb-3"></i>
                        <div>Aucune notification pour le moment.</div>
                        <div class="small">Les notifications valides apparaissent ici.</div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                                $type = htmlspecialchars($notification['type'] ?? 'Info');
                                $title = htmlspecialchars($notification['title'] ?: 'Notification');
                                $messageText = htmlspecialchars($notification['message'] ?? '');
                                $createdAt = isset($notification['created_at']) ? date('d/m/Y H:i', strtotime($notification['created_at'])) : '-';
                                $scheduledAt = isset($notification['scheduled_at']) && $notification['scheduled_at'] ? date('d/m/Y H:i', strtotime($notification['scheduled_at'])) : null;
                                $limitedAt = isset($notification['limited_at']) && $notification['limited_at'] ? date('d/m/Y H:i', strtotime($notification['limited_at'])) : null;
                                $recipients = htmlspecialchars($notification['recipients'] ?? 'Tous');
                            ?>
                            <div class="list-group-item list-group-item-action mb-2 rounded shadow-sm">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= $title ?></div>
                                        <div class="text-muted small">Envoyée le <?= $createdAt ?></div>
                                    </div>
                                    <span class="badge bg-secondary"><?= $type ?></span>
                                </div>
                                <div class="mt-3">
                                    <?= nl2br($messageText) ?>
                                </div>
                                <div class="mt-3 d-flex flex-wrap gap-2 small text-muted">
                                    <?php if ($scheduledAt): ?>
                                        <span><i class="bi bi-calendar-event"></i> Programmée : <?= $scheduledAt ?></span>
                                    <?php endif; ?>
                                    <?php if ($limitedAt): ?>
                                        <span><i class="bi bi-hourglass-split"></i> Valide jusqu’au : <?= $limitedAt ?></span>
                                    <?php endif; ?>
                                    <span><i class="bi bi-people"></i> Destinataires : <?= $recipients ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Conseil</h5>
                <p class="card-text">Consultez régulièrement vos notifications pour ne manquer aucune information importante de Silver Happy.</p>
            </div>
        </div>
    </div>
</div>

<?php include './include/footer.php'; ?>
