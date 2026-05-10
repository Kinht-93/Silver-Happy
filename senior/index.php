<?php
$seniorCurrent = 'dashboard';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$upcomingEventsCount = 0;
$pendingRequestsCount = 0;
$receivedMessagesCount = 0;
$alertText = "Vous n'êtes inscrit à aucun événement pour le moment";
$alertLinkLabel = "Découvrir l'agenda";

if ($token !== '' && $userId !== '') {
    $registrations = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/event-registrations', 'GET', null, $token);
    $events = callAPI('http://localhost:8080/api/events', 'GET', null, $token);
    $serviceRequests = callAPI('http://localhost:8080/api/service-requests?id_user=' . urlencode($userId), 'GET', null, $token);
    $messages = callAPI('http://localhost:8080/api/messages?id_user=' . urlencode($userId), 'GET', null, $token);

    if (is_array($registrations) && !isset($registrations['error']) && is_array($events) && !isset($events['error'])) {
        $eventsById = [];
        foreach ($events as $event) {
            if (!empty($event['id_event'])) {
                $eventsById[(string)$event['id_event']] = $event;
            }
        }

        $now = time();
        foreach ($registrations as $registration) {
            $eventId = (string)($registration['id_event'] ?? '');
            $registrationStatus = $registration['status'] ?? '';
            if ($eventId === '' || !isset($eventsById[$eventId])) {
                continue;
            }
            if (in_array($registrationStatus, ['annule', 'annulee', 'refuse', 'refusee'], true)) {
                continue;
            }

            $eventDate = strtotime((string)($eventsById[$eventId]['start_date'] ?? ''));
            if ($eventDate !== false && $eventDate >= $now) {
                $upcomingEventsCount++;
            }
        }
    }

    if (is_array($serviceRequests) && !isset($serviceRequests['error'])) {
        foreach ($serviceRequests as $request) {
            $status = $request['status'] ?? '';
            if (!in_array($status, ['termine', 'annule', 'annulee', 'refuse', 'refusee', 'clos', 'ferme', 'fermee'], true)) {
                $pendingRequestsCount++;
            }
        }
    }

    if (is_array($messages) && !isset($messages['error'])) {
        foreach ($messages as $message) {
            if ((string)($message['receiver'] ?? '') === $userId) {
                $receivedMessagesCount++;
            }
        }
    }

    if ($upcomingEventsCount > 0) {
        $alertText = $upcomingEventsCount === 1
            ? 'Vous avez 1 événement à venir dans votre agenda'
            : 'Vous avez ' . $upcomingEventsCount . ' événements à venir dans votre agenda';
        $alertLinkLabel = 'Voir mon agenda';
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Tableau de bord</h1>
            <p class="senier-subtitle">Bienvenue dans votre espace personnel.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Tableau de bord</div>
    </div>

    <div>
            <div class="senier-home-kpis">
                <div class="senier-home-kpi">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <strong><?= $upcomingEventsCount ?></strong>
                    <span>Événements à venir</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-clock-fill text-warning"></i>
                    <strong><?= $pendingRequestsCount ?></strong>
                    <span>Demandes en cours</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-envelope-fill text-success"></i>
                    <strong><?= $receivedMessagesCount ?></strong>
                    <span><?= $receivedMessagesCount > 1 ? 'Messages reçus' : 'Message reçu' ?></span>
                </div>
            </div>

            <div class="senier-home-alert mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <?= htmlspecialchars($alertText) ?>
                    <a href="planning.php"><?= htmlspecialchars($alertLinkLabel) ?></a>
                </div>
            </div>

            <div class="senier-home-grid">
                <a class="senier-home-card" href="planning.php">
                    <h3><i class="bi bi-calendar3"></i> Planning</h3>
                    <p>Consultez votre agenda et vos prochains rendez-vous.</p>
                </a>
                <a class="senier-home-card" href="prestation.php">
                    <h3><i class="bi bi-briefcase"></i> Prestations</h3>
                    <p>Accédez rapidement aux services disponibles.</p>
                </a>
                <a class="senier-home-card" href="messagerie.php">
                    <h3><i class="bi bi-chat-dots"></i> Messagerie</h3>
                    <p>Échangez avec les prestataires et l'équipe.</p>
                </a>
                <a class="senier-home-card" href="mon-profil.php">
                    <h3><i class="bi bi-person-circle"></i> Mon profil</h3>
                    <p>Mettez à jour vos informations personnelles.</p>
                </a>
                <a class="senier-home-card" href="../mes-factures.php">
                    <h3><i class="bi bi-receipt"></i> Mes factures</h3>
                    <p>Retrouvez l'historique de vos paiements.</p>
                </a>
                <a class="senier-home-card" href="contact.php">
                    <h3><i class="bi bi-headset"></i> Contact</h3>
                    <p>Besoin d'aide ? Notre équipe reste disponible.</p>
                </a>
            </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
