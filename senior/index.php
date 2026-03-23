<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'dashboard';
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$upcomingEventsCount = 0;
$pendingRequestsCount = 0;
$unreadMessagesCount = 0;
$nextPlanningUrl = 'planning.php';

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $eventsStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM event_registrations er
             INNER JOIN events e ON e.id_event = er.id_event
             WHERE er.id_user = ?
               AND er.status IN ('Confirmee', 'Validee', 'En attente')
               AND e.start_date >= NOW()"
        );
        $eventsStmt->execute([$userId]);
        $upcomingEventsCount = (int)$eventsStmt->fetchColumn();

        $requestsStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM service_requests
             WHERE id_user = ?
               AND status IN ('En attente', 'Planifiee', 'En cours')"
        );
        $requestsStmt->execute([$userId]);
        $pendingRequestsCount = (int)$requestsStmt->fetchColumn();

        $messagesStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM messages
             WHERE receiver = ?"
        );
        $messagesStmt->execute([$userId]);
        $unreadMessagesCount = (int)$messagesStmt->fetchColumn();
    } catch (Throwable $e) {
    }
}

if ($upcomingEventsCount > 0) {
    $nextPlanningUrl = 'evenements-inscriptions.php';
} elseif ($pendingRequestsCount > 0) {
    $nextPlanningUrl = 'prestations-demandes.php';
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
                    <strong><?= (int)$upcomingEventsCount ?></strong>
                    <span>Événements à venir</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-clock-fill text-warning"></i>
                    <strong><?= (int)$pendingRequestsCount ?></strong>
                    <span>Demandes en cours</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-envelope-fill text-success"></i>
                    <strong><?= (int)$unreadMessagesCount ?></strong>
                    <span>Nouveau message</span>
                </div>
            </div>

            <div class="senier-home-alert mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <?php if ($upcomingEventsCount === 0): ?>
                        Vous n'êtes inscrit à aucun événement pour le moment.
                        <a href="evenements-liste.php">Découvrir l'agenda</a>
                    <?php else: ?>
                        Vous avez des événements planifiés ce mois-ci.
                        <a href="evenements-inscriptions.php">Voir mes inscriptions</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="senier-home-grid">
                <a class="senier-home-card" href="<?= htmlspecialchars($nextPlanningUrl) ?>">
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
                <a class="senier-home-card" href="mes-factures.php">
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
