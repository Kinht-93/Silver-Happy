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
    $idEvent  = $_POST['id_event'] ?? '';
    $response = callAPI(
        'http://localhost:8080/api/events/' . urlencode($idEvent) . '/checkout',
        'POST',
        ['id_user' => $userId],
        $token
    );

    if (isset($response['error'])) {
        $message     = htmlspecialchars($response['error']);
        $messageType = 'danger';
    } elseif (!empty($response['checkout_url'])) {
        header('Location: ' . $response['checkout_url']);
        exit;
    } else {
        $message     = 'Inscription enregistrée avec succès.';
        $messageType = 'success';
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
        <div class="senior-panel">
            <p class="mb-0">Aucun événement disponible pour le moment.</p>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
