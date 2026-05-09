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
$availableEvents = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register' && $token !== '' && $userId !== '') {
    $response = callAPI('http://localhost:8080/api/event-registrations', 'POST', [
        'status' => 'Inscrit',
        'paid' => false,
        'id_user' => $userId,
        'id_event' => $_POST['id_event'] ?? '',
    ], $token);

    if (is_array($response) && !isset($response['error'])) {
        $message = 'Inscription enregistrée avec succès.';
        $messageType = 'success';
    } else {
        $message = 'Impossible de vous inscrire à cet événement.';
        $messageType = 'danger';
    }
}

if ($token !== '' && $userId !== '') {
    $events = callAPI('http://localhost:8080/api/events', 'GET', null, $token);
    $registrations = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/event-registrations', 'GET', null, $token);

    $registeredEventIds = [];
    if (is_array($registrations) && !isset($registrations['error'])) {
        foreach ($registrations as $registration) {
            $registeredEventIds[(string)($registration['id_event'] ?? '')] = true;
        }
    }

    if (is_array($events) && !isset($events['error'])) {
        $now = time();
        foreach ($events as $event) {
            $eventId = (string)($event['id_event'] ?? '');
            $eventDate = strtotime((string)($event['start_date'] ?? ''));
            if ($eventId === '' || $eventDate === false || $eventDate < $now || isset($registeredEventIds[$eventId])) {
                continue;
            }
            $availableEvents[] = $event;
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Liste des événements</h1>
        <p class="senior-subtitle">Inscrivez-vous aux prochains événements disponibles.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="senior-panel">
            <?php if (empty($availableEvents)): ?>
                <p class="mb-0">Aucun événement disponible pour le moment.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($availableEvents as $event): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 bg-white">
                                <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                                    <h2 class="h5 mb-0"><?= htmlspecialchars($event['title'] ?? 'Événement') ?></h2>
                                    <span class="badge text-bg-light"><?= htmlspecialchars($event['event_type'] ?? 'Événement') ?></span>
                                </div>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($event['start_date'] ?? 'now')))) ?>
                                </p>
                                <p class="mb-3">
                                    <strong>Places :</strong> <?= (int)($event['max_places'] ?? 0) ?>
                                    <br>
                                    <strong>Prix :</strong> <?= number_format((float)($event['price'] ?? 0), 2) ?> €
                                </p>
                                <form method="POST" class="mt-auto">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="id_event" value="<?= htmlspecialchars($event['id_event'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">S'inscrire</button>
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
