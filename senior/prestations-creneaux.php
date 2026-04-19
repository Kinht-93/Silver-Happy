<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

if (!function_exists('sh_slug_service')) {
    function sh_slug_service($value)
    {
        $slug = strtolower(trim((string)$value));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim((string)$slug, '_');
        return $slug !== '' ? $slug : 'service';
    }
}

if (!function_exists('sh_resolve_or_create_category')) {
    function sh_resolve_or_create_category($pdo, $categoryId, $categoryName)
    {
        if (!$pdo instanceof PDO) {
            return null;
        }

        $categoryId = trim((string)$categoryId);
        $categoryName = trim((string)$categoryName);

        if ($categoryId !== '') {
            $byIdStmt = $pdo->prepare(
                "SELECT id_service_category, name, description
                 FROM service_categories
                 WHERE id_service_category = ?
                 LIMIT 1"
            );
            $byIdStmt->execute([$categoryId]);
            $row = $byIdStmt->fetch();
            if ($row) {
                return $row;
            }
        }

        if ($categoryName !== '') {
            $byNameStmt = $pdo->prepare(
                "SELECT id_service_category, name, description
                 FROM service_categories
                 WHERE LOWER(name) = LOWER(?)
                 LIMIT 1"
            );
            $byNameStmt->execute([$categoryName]);
            $row = $byNameStmt->fetch();
            if ($row) {
                return $row;
            }

            $baseId = $categoryId !== '' ? $categoryId : 'cat_' . sh_slug_service($categoryName);
            $candidateId = $baseId;
            $suffix = 1;

            while (true) {
                try {
                    $insertStmt = $pdo->prepare(
                        'INSERT INTO service_categories (id_service_category, name, description)
                         VALUES (?, ?, ?)' 
                    );
                    $insertStmt->execute([
                        $candidateId,
                        $categoryName,
                        'Categorie de service',
                    ]);

                    return [
                        'id_service_category' => $candidateId,
                        'name' => $categoryName,
                        'description' => 'Categorie de service',
                    ];
                } catch (PDOException $e) {
                    if ((string)$e->getCode() !== '23000') {
                        throw $e;
                    }
                    $suffix++;
                    $candidateId = $baseId . '_' . $suffix;
                    if ($suffix > 20) {
                        return null;
                    }
                }
            }
        }

        return null;
    }
}

$seniorCurrent = 'prestation';
$message = '';
$messageType = '';
$availableSlots = [];
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$seniorFullName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));

$selectedCategoryId = trim((string)($_GET['category_id'] ?? $_POST['category_id'] ?? ''));
$selectedCategoryName = trim((string)($_GET['category_name'] ?? $_POST['category_name'] ?? ''));
$selectedCategory = null;

if ($pdo instanceof PDO && $selectedCategoryId !== '') {
    try {
        $selectedCategory = sh_resolve_or_create_category($pdo, $selectedCategoryId, $selectedCategoryName);
        if ($selectedCategory) {
            $selectedCategoryId = (string)$selectedCategory['id_service_category'];
            $selectedCategoryName = (string)$selectedCategory['name'];
        }
    } catch (Throwable $e) {
        $selectedCategory = null;
    }
} elseif ($pdo instanceof PDO && $selectedCategoryName !== '') {
    try {
        $selectedCategory = sh_resolve_or_create_category($pdo, '', $selectedCategoryName);
        if ($selectedCategory) {
            $selectedCategoryId = (string)$selectedCategory['id_service_category'];
            $selectedCategoryName = (string)$selectedCategory['name'];
        }
    } catch (Throwable $e) {
        $selectedCategory = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'reserve') {
        $availabilityId = (int)($_POST['id_availability'] ?? 0);

        try {
            if ($availabilityId <= 0 || $userId === '') {
                throw new RuntimeException('Reservation invalide.');
            }

            if ($selectedCategoryId === '' || !$selectedCategory) {
                throw new RuntimeException('Veuillez choisir un type de service valide.');
            }

            $pdo->beginTransaction();

            $slotStmt = $pdo->prepare(
                "SELECT pa.id_availability, pa.id_user, pa.available_date, pa.start_time, pa.end_time,
                        u.company_name, u.first_name, u.last_name
                 FROM provider_availabilities pa
                 INNER JOIN users u ON u.id_user = pa.id_user
                 WHERE pa.id_availability = ?
                   AND pa.is_available = 1
                   AND u.role = 'prestataire'
                   AND (pa.available_date > CURDATE() OR (pa.available_date = CURDATE() AND pa.start_time > CURTIME()))
                 LIMIT 1"
            );
            $slotStmt->execute([$availabilityId]);
            $slot = $slotStmt->fetch();

            if (!$slot) {
                throw new RuntimeException('Ce creneau n est plus disponible.');
            }

            $lockStmt = $pdo->prepare('UPDATE provider_availabilities SET is_available = 0 WHERE id_availability = ? AND is_available = 1');
            $lockStmt->execute([$availabilityId]);
            if ($lockStmt->rowCount() !== 1) {
                throw new RuntimeException('Ce creneau vient d etre reserve par un autre utilisateur.');
            }

            $durationSeconds = strtotime((string)$slot['end_time']) - strtotime((string)$slot['start_time']);
            $durationHours = (int)max(1, ceil($durationSeconds / 3600));
            $providerLabel = trim((string)(($slot['first_name'] ?? '') . ' ' . ($slot['last_name'] ?? '')));
            if ($providerLabel === '') {
                $providerLabel = (string)($slot['company_name'] ?? 'Prestataire');
            }

            $requestId = 'req_' . bin2hex(random_bytes(8));
            $requestAddress = 'Service: ' . (string)$selectedCategory['name'] . ' | Prestataire: ' . $providerLabel;

            $insertRequest = $pdo->prepare(
                'INSERT INTO service_requests (id_request, desired_date, start_time, estimated_duration, intervention_address, status, created_at, id_user, id_service_category)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)'
            );
            $insertRequest->execute([
                $requestId,
                $slot['available_date'],
                $slot['start_time'],
                $durationHours,
                mb_substr($requestAddress, 0, 255),
                'En attente',
                $userId,
                $selectedCategoryId,
            ]);

            $missionId = 'mis_' . bin2hex(random_bytes(8));
            $missionTitle = 'Demande senior - ' . (string)$selectedCategory['name'];
            $missionDescription = 'Senior: ' . ($seniorFullName !== '' ? $seniorFullName : $userId)
                . ' | Service: ' . (string)$selectedCategory['name']
                . ' | Creneau: ' . (string)$slot['available_date'] . ' ' . substr((string)$slot['start_time'], 0, 5)
                . '-' . substr((string)$slot['end_time'], 0, 5);

            $insertMission = $pdo->prepare(
                "INSERT INTO provider_missions (id_mission, mission_title, mission_description, mission_date, status, id_user, accepted_at, created_at)
                 VALUES (?, ?, ?, ?, 'Acceptee', ?, NOW(), NOW())"
            );
            $insertMission->execute([
                $missionId,
                $missionTitle,
                mb_substr($missionDescription, 0, 1000),
                $slot['available_date'],
                $slot['id_user'],
            ]);

            $pdo->commit();

            header('Location: planning.php?reserved=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $e->getMessage() !== '' ? $e->getMessage() : 'Impossible de reserver ce creneau.';
            $messageType = 'danger';
        }
    }
}

if ($pdo instanceof PDO) {
    try {
        $slotsStmt = $pdo->query(
            "SELECT pa.id_availability, pa.available_date, pa.start_time, pa.end_time,
                    u.company_name, u.first_name, u.last_name
             FROM provider_availabilities pa
             INNER JOIN users u ON u.id_user = pa.id_user
             WHERE pa.is_available = 1
               AND u.role = 'prestataire'
               AND (pa.available_date > CURDATE() OR (pa.available_date = CURDATE() AND pa.start_time > CURTIME()))
             ORDER BY pa.available_date ASC, pa.start_time ASC"
        );
        $availableSlots = $slotsStmt ? $slotsStmt->fetchAll() : [];
    } catch (PDOException $e) {
        $message = 'Impossible de charger les disponibilites des prestataires.';
        $messageType = 'danger';
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Creneaux disponibles</h1>
            <p class="senier-subtitle">
                Service choisi:
                <strong><?= htmlspecialchars((string)($selectedCategory['name'] ?? 'Non defini')) ?></strong>
            </p>
        </div>
        <div class="senier-breadcrumb">Accueil/Prestations/Creneaux</div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType !== '' ? $messageType : 'info') ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$selectedCategory): ?>
        <div class="alert alert-warning" role="alert">
            Type de service invalide. <a href="prestation.php">Retourner a la liste des services</a>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Prestataires et disponibilites</h5>
                    <a class="btn btn-outline-secondary btn-sm" href="prestation.php">Changer de service</a>
                </div>

                <?php if (empty($availableSlots)): ?>
                    <p class="mb-0">Aucun creneau disponible pour le moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Prestataire</th>
                                    <th>Entreprise</th>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableSlots as $slot): ?>
                                    <?php $providerName = trim((string)(($slot['first_name'] ?? '') . ' ' . ($slot['last_name'] ?? ''))); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($providerName !== '' ? $providerName : 'Prestataire') ?></td>
                                        <td><?= htmlspecialchars((string)($slot['company_name'] ?? 'N/A')) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)$slot['available_date']))) ?></td>
                                        <td><?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Confirmer la reservation de ce creneau ?');">
                                                <input type="hidden" name="action" value="reserve">
                                                <input type="hidden" name="id_availability" value="<?= (int)$slot['id_availability'] ?>">
                                                <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$selectedCategory['id_service_category']) ?>">
                                                <input type="hidden" name="category_name" value="<?= htmlspecialchars((string)$selectedCategory['name']) ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Reserver</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php include './include/footer.php'; ?>
