<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'planning';
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$bookedAppointments = [];
$bookedEvents = [];
$loadError = '';

$monthParam = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}

[$yearStr, $monthStr] = explode('-', $monthParam);
$year = (int)$yearStr;
$month = (int)$monthStr;
if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    $year = (int)date('Y');
    $month = (int)date('m');
}

$firstDay = sprintf('%04d-%02d-01', $year, $month);
$firstDate = new DateTime($firstDay);
$daysInMonth = (int)$firstDate->format('t');
$startWeekday = (int)$firstDate->format('N'); // 1=lundi ... 7=dimanche
$prevMonth = (clone $firstDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $firstDate)->modify('+1 month')->format('Y-m');

$monthNames = [
    1 => 'Janvier',
    2 => 'Fevrier',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Aout',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Decembre',
];
$monthLabel = ($monthNames[$month] ?? 'Mois') . ' ' . $year;
$appointmentsByDay = [];
$eventsByDay = [];

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $stmt = $pdo->prepare(
            "SELECT sr.desired_date, sr.start_time, sr.estimated_duration, sr.intervention_address, sr.status,
                    sc.name AS category_name
             FROM service_requests sr
             INNER JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
             WHERE sr.id_user = ?
               AND sr.desired_date BETWEEN ? AND ?
             ORDER BY sr.desired_date ASC, sr.start_time ASC"
        );
        $lastDay = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        $stmt->execute([$userId, $firstDay, $lastDay]);
        $bookedAppointments = $stmt->fetchAll();

        foreach ($bookedAppointments as $appointment) {
            $day = (int)date('j', strtotime((string)$appointment['desired_date']));
            if (!isset($appointmentsByDay[$day])) {
                $appointmentsByDay[$day] = [];
            }
            $appointmentsByDay[$day][] = $appointment;
        }

        $eventsStmt = $pdo->prepare(
            "SELECT e.title, e.start_date, e.end_date, er.status
             FROM event_registrations er
             INNER JOIN events e ON e.id_event = er.id_event
             WHERE er.id_user = ?
               AND e.start_date BETWEEN ? AND ?
             ORDER BY e.start_date ASC"
        );
        $eventsStmt->execute([
            $userId,
            $firstDay . ' 00:00:00',
            $lastDay . ' 23:59:59',
        ]);
        $bookedEvents = $eventsStmt->fetchAll();

        foreach ($bookedEvents as $event) {
            $day = (int)date('j', strtotime((string)$event['start_date']));
            if (!isset($eventsByDay[$day])) {
                $eventsByDay[$day] = [];
            }
            $eventsByDay[$day][] = $event;
        }
    } catch (PDOException $e) {
        $loadError = 'Impossible de charger votre planning.';
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Planning</h1>
            <p class="senier-subtitle">Retrouvez vos événements et prestations planifiés.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Planning</div>
    </div>

    <?php if (isset($_GET['reserved']) && $_GET['reserved'] === '1'): ?>
        <div class="alert alert-success" role="alert">Votre rendez-vous a bien ete reserve.</div>
    <?php endif; ?>

    <?php if ($loadError): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($loadError) ?></div>
    <?php endif; ?>

    <div class="senier-layout">
        <aside>
            <div class="senier-legend mb-2">
                <h4>Légende :</h4>
                <div class="senier-legend-item"><span class="senier-dot" style="background:#4f46e5;"></span> Événements</div>
                <div class="senier-legend-item"><span class="senier-dot" style="background:#10b981;"></span> Prestations</div>
                <div class="senier-legend-item mb-0"><span class="senier-dot" style="background:#0ea5e9;"></span> RV médical</div>
            </div>
            <a href="evenements-liste.php" class="btn btn-success btn-sm w-100 mb-2">S'inscrire a un evenement</a>
            <a href="prestation.php" class="btn btn-outline-success btn-sm w-100">Demander une prestation</a>
        </aside>

        <div class="senier-panel">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h3 class="senier-panel-title mb-0"><?= htmlspecialchars($monthLabel) ?></h3>
                <div class="senier-calendar-nav">
                    <a href="planning.php?month=<?= urlencode($prevMonth) ?>" class="btn btn-outline-success btn-sm">&lt;</a>
                    <a href="planning.php?month=<?= urlencode($nextMonth) ?>" class="btn btn-outline-success btn-sm">&gt;</a>
                </div>
            </div>

            <table class="senier-calendar">
                <thead>
                    <tr>
                        <th>Lun</th>
                        <th>Mar</th>
                        <th>Mer</th>
                        <th>Jeu</th>
                        <th>Ven</th>
                        <th>Sam</th>
                        <th>Dim</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $offset = $startWeekday - 1;
                    $totalCells = (int)(ceil(($offset + $daysInMonth) / 7) * 7);
                    for ($cell = 0; $cell < $totalCells; $cell++):
                        if ($cell % 7 === 0) {
                            echo '<tr>';
                        }

                        $dayNumber = $cell - $offset + 1;
                        if ($dayNumber < 1 || $dayNumber > $daysInMonth) {
                            echo '<td></td>';
                        } else {
                            $isToday = ((int)date('Y') === $year && (int)date('n') === $month && (int)date('j') === $dayNumber);
                            echo '<td style="vertical-align:top; min-height:90px;">';
                            echo '<div style="font-weight:600;' . ($isToday ? ' color:#0d6efd;' : '') . '">' . $dayNumber . '</div>';

                            if (!empty($appointmentsByDay[$dayNumber])) {
                                foreach ($appointmentsByDay[$dayNumber] as $appt) {
                                    $slot = htmlspecialchars(substr((string)$appt['start_time'], 0, 5));
                                    echo '<div style="margin-top:4px; padding:4px 6px; border-radius:6px; background:#dcfce7; color:#166534; font-size:12px;">';
                                    echo '<strong>' . $slot . '</strong> - ' . htmlspecialchars((string)$appt['category_name']);
                                    echo '</div>';
                                }
                            }

                            if (!empty($eventsByDay[$dayNumber])) {
                                foreach ($eventsByDay[$dayNumber] as $event) {
                                    $slot = htmlspecialchars(date('H:i', strtotime((string)$event['start_date'])));
                                    echo '<div style="margin-top:4px; padding:4px 6px; border-radius:6px; background:#dbeafe; color:#1d4ed8; font-size:12px;">';
                                    echo '<strong>' . $slot . '</strong> - ' . htmlspecialchars((string)$event['title']);
                                    echo '</div>';
                                }
                            }

                            echo '</td>';
                        }

                        if ($cell % 7 === 6) {
                            echo '</tr>';
                        }
                    endfor;
                    ?>
                </tbody>
            </table>

            <?php if (empty($bookedAppointments) && empty($bookedEvents)): ?>
                <p class="mt-2 mb-0 text-muted">Aucun rendez-vous ou evenement reserve sur ce mois.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
