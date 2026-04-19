<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'prestations';
$errors = [];
$categoryOptions = [];
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$selectedCategoryId = trim((string)($_POST['id_service_category'] ?? ''));
$desiredDate = trim((string)($_POST['desired_date'] ?? ''));
$startTime = trim((string)($_POST['start_time'] ?? ''));
$estimatedDuration = (int)($_POST['estimated_duration'] ?? 1);
$interventionAddress = trim((string)($_POST['intervention_address'] ?? ''));
$requestMessage = trim((string)($_POST['request_message'] ?? ''));

if ($pdo instanceof PDO) {
    try {
        $countCategories = (int)$pdo->query('SELECT COUNT(*) FROM service_categories')->fetchColumn();
        if ($countCategories === 0) {
            $defaultCategories = ['Menage', 'Assistance', 'Transport', 'Informatique', 'Sante', 'Courses', 'Animation', 'Accompagnement'];
            $insertCategory = $pdo->prepare('INSERT INTO service_categories (id_service_category, name, description) VALUES (?, ?, ?)');
            foreach ($defaultCategories as $name) {
                $id = 'cat_' . strtolower($name);
                try {
                    $insertCategory->execute([$id, $name, 'Categorie de prestation']);
                } catch (PDOException $e) {
                }
            }
        }

        $categoriesStmt = $pdo->query('SELECT id_service_category, name FROM service_categories ORDER BY name ASC');
        $categoryOptions = $categoriesStmt ? $categoriesStmt->fetchAll() : [];
    } catch (PDOException $e) {
        $errors[] = 'Erreur de chargement des categories.';
    }
}

if ($selectedCategoryId === '' && isset($_GET['category']) && $pdo instanceof PDO) {
    $categoryName = trim((string)$_GET['category']);
    if ($categoryName !== '') {
        foreach ($categoryOptions as $option) {
            if (strcasecmp((string)$option['name'], $categoryName) === 0) {
                $selectedCategoryId = (string)$option['id_service_category'];
                break;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pdo instanceof PDO) {
        $errors[] = 'Base de donnees indisponible.';
    }
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($selectedCategoryId === '') {
        $errors[] = 'Le type de prestation est obligatoire.';
    }
    if ($desiredDate === '') {
        $errors[] = 'La date souhaitee est obligatoire.';
    }
    if ($startTime === '') {
        $errors[] = 'L heure souhaitee est obligatoire.';
    }
    if ($estimatedDuration < 1 || $estimatedDuration > 12) {
        $errors[] = 'La duree doit etre comprise entre 1 et 12 heures.';
    }
    if ($interventionAddress === '') {
        $errors[] = 'L adresse d intervention est obligatoire.';
    }

    if ($desiredDate !== '' && $desiredDate < date('Y-m-d')) {
        $errors[] = 'La date de prestation doit etre aujourd hui ou dans le futur.';
    }

    if ($desiredDate === date('Y-m-d') && $startTime !== '' && strlen($startTime) >= 5) {
        $startWithSeconds = substr($startTime, 0, 5) . ':00';
        if ($startWithSeconds <= date('H:i:s')) {
            $errors[] = 'Pour aujourd hui, l heure de debut doit etre dans le futur.';
        }
    }

    $validCategoryIds = array_map(static fn($c) => (string)$c['id_service_category'], $categoryOptions);
    if ($selectedCategoryId !== '' && !in_array($selectedCategoryId, $validCategoryIds, true)) {
        $errors[] = 'Categorie de prestation invalide.';
    }

    if (empty($errors)) {
        try {
            $idRequest = 'req_' . bin2hex(random_bytes(8));
            $addressWithNote = $interventionAddress;
            if ($requestMessage !== '') {
                $addressWithNote .= ' | Note: ' . $requestMessage;
            }
            $addressWithNote = mb_substr($addressWithNote, 0, 255);

            $insertStmt = $pdo->prepare(
                'INSERT INTO service_requests (id_request, desired_date, start_time, estimated_duration, intervention_address, status, created_at, id_user, id_service_category)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)'
            );
            $insertStmt->execute([
                $idRequest,
                $desiredDate,
                $startTime,
                $estimatedDuration,
                $addressWithNote,
                'En attente',
                $userId,
                $selectedCategoryId,
            ]);

            header('Location: prestations-demandes.php?created=1');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Impossible d enregistrer la demande pour le moment.';
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Demander une prestation</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="senior-form" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="id_service_category">Type de prestation</label>
                    <select class="form-control" id="id_service_category" name="id_service_category" required>
                        <option value="">Selectionner...</option>
                        <?php foreach ($categoryOptions as $category): ?>
                            <option value="<?= htmlspecialchars((string)$category['id_service_category']) ?>" <?= $selectedCategoryId === (string)$category['id_service_category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="desired_date">Date souhaitee</label>
                    <input class="form-control" id="desired_date" name="desired_date" type="date" value="<?= htmlspecialchars($desiredDate) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="start_time">Heure de debut</label>
                    <input class="form-control" id="start_time" name="start_time" type="time" value="<?= htmlspecialchars($startTime) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="estimated_duration">Duree (heures)</label>
                    <input class="form-control" id="estimated_duration" name="estimated_duration" type="number" min="1" max="12" value="<?= (int)$estimatedDuration ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="intervention_address">Adresse d intervention</label>
                    <input class="form-control" id="intervention_address" name="intervention_address" type="text" value="<?= htmlspecialchars($interventionAddress) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="request_message">Details de la demande</label>
                    <textarea class="form-control" id="request_message" name="request_message" rows="4"><?= htmlspecialchars($requestMessage) ?></textarea>
                </div>
            </div>
            <div class="senior-actions">
                <button class="btn btn-success" type="submit">Envoyer la demande</button>
                <a class="btn btn-outline-secondary" href="prestations.php">Retour</a>
            </div>
        </form>
    </div>
</section>

<?php include './include/footer.php'; ?>
