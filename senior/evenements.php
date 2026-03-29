<?php
$seniorCurrent = 'evenements';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$availableCount = 0;
$upcomingRegistrationsCount = 0;
$historyCount = 0;

if ($token !== '' && $userId !== '') {
    $events = callAPI('http://localhost:8080/api/events', 'GET', null, $token);
    $registrations = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/event-registrations', 'GET', null, $token);

    $now = time();
    $registeredEventIds = [];

    if (is_array($registrations) && !isset($registrations['error'])) {
        foreach ($registrations as $registration) {
            $registeredEventIds[(string)($registration['id_event'] ?? '')] = true;
        }
    }

    if (is_array($events) && !isset($events['error'])) {
        foreach ($events as $event) {
            $eventId = (string)($event['id_event'] ?? '');
            $eventDate = strtotime((string)($event['start_date'] ?? ''));
            if ($eventId === '' || $eventDate === false) {
                continue;
            }

            if ($eventDate >= $now) {
                if (!isset($registeredEventIds[$eventId])) {
                    $availableCount++;
                } else {
                    $upcomingRegistrationsCount++;
                }
            } elseif (isset($registeredEventIds[$eventId])) {
                $historyCount++;
            }
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Événements</h1>
        <p class="senior-subtitle">Consultez les événements et suivez vos participations.</p>

        <div class="senior-grid">
            <a class="senior-card" href="evenements-liste.php"><h2>Liste des événements</h2><p><?= $availableCount ?> événement<?= $availableCount > 1 ? 's' : '' ?> disponible<?= $availableCount > 1 ? 's' : '' ?> à venir.</p></a>
            <a class="senior-card" href="evenements-inscriptions.php"><h2>Mes inscriptions</h2><p><?= $upcomingRegistrationsCount ?> inscription<?= $upcomingRegistrationsCount > 1 ? 's' : '' ?> active<?= $upcomingRegistrationsCount > 1 ? 's' : '' ?>.</p></a>
            <a class="senior-card" href="evenements-historique.php"><h2>Historique</h2><p><?= $historyCount ?> participation<?= $historyCount > 1 ? 's' : '' ?> passée<?= $historyCount > 1 ? 's' : '' ?>.</p></a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
