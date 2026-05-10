<?php
$seniorCurrent = 'evenements';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$historyItems = [];

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
            if ($eventDate === false || $eventDate >= $now) {
                continue;
            }
            $historyItems[] = [
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
        <h1 class="senior-title">Historique des événements</h1>
        <p class="senior-subtitle">Vos événements passés et vos anciennes participations.</p>

        <div class="senior-panel">
            <?php if (empty($historyItems)): ?>
                <p class="mb-0">Aucun historique disponible pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Événement</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Inscription</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyItems as $item): ?>
                                <?php $event = $item['event']; $registration = $item['registration']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['title'] ?? 'Événement') ?></td>
                                    <td><?= htmlspecialchars($event['event_type'] ?? 'Événement') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($event['start_date'] ?? 'now')))) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)($registration['registration_date'] ?? 'now')))) ?></td>
                                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($registration['status'] ?? 'Traité') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
