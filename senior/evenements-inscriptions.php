<?php
$seniorCurrent = 'evenements';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$message = '';
$messageType = '';
$upcomingRegistrations = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel' && $token !== '') {
    $response = callAPI('http://localhost:8080/api/event-registrations/' . urlencode($_POST['id_registration'] ?? ''), 'DELETE', null, $token);
    if (!is_array($response) || !isset($response['error'])) {
        $message = 'Inscription annulée.';
        $messageType = 'success';
    } else {
        $message = 'Impossible d\'annuler cette inscription.';
        $messageType = 'danger';
    }
}

if ($token !== '' && $userId !== '') {
    $events = callAPI('http://localhost:8080/api/events', 'GET', null, $token);
    $registrations = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/event-registrations', 'GET', null, $token);

    $eventsById = [];
    if (is_array($events) && !isset($events['error'])) {
        foreach ($events as $event) {
            if (!empty($event['id_event'])) {
                $eventsById[(string)$event['id_event']] = $event;
            }
        }
    }

    if (is_array($registrations) && !isset($registrations['error'])) {
        $now = time();
        foreach ($registrations as $registration) {
            $eventId = (string)($registration['id_event'] ?? '');
            if (!isset($eventsById[$eventId])) {
                continue;
            }
            $event = $eventsById[$eventId];
            $eventDate = strtotime((string)($event['start_date'] ?? ''));
            if ($eventDate === false || $eventDate < $now) {
                continue;
            }
            $upcomingRegistrations[] = [
                'registration' => $registration,
                'event' => $event,
            ];
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Mes inscriptions</h1>
        <p class="senior-subtitle">Retrouvez ici vos événements à venir.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="senior-panel">
            <?php if (empty($upcomingRegistrations)): ?>
                <p class="mb-0">Vous n'êtes inscrit à aucun événement pour le moment.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($upcomingRegistrations as $item): ?>
                        <?php $event = $item['event']; $registration = $item['registration']; ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 bg-white">
                                <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                                    <h2 class="h5 mb-0"><?= htmlspecialchars($event['title'] ?? 'Événement') ?></h2>
                                    <span class="badge text-bg-success"><?= htmlspecialchars($registration['status'] ?? 'Inscrit') ?></span>
                                </div>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($event['start_date'] ?? 'now')))) ?>
                                </p>
                                <p class="mb-3">
                                    <strong>Type :</strong> <?= htmlspecialchars($event['event_type'] ?? 'Événement') ?>
                                    <br>
                                    <strong>Prix :</strong> <?= number_format((float)($event['price'] ?? 0), 2) ?> €
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id_registration" value="<?= htmlspecialchars($registration['id_registration'] ?? '') ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Annuler cette inscription ?');">Annuler l'inscription</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
