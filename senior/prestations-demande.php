<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'prestations';
$errors = [];
$categoryOptions = [];
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$selectedCategoryId = trim((string)($_POST['id_service_category'] ?? ''));
$desiredDate = trim((string)($_POST['desired_date'] ?? ''));
$startTime = trim((string)($_POST['start_time'] ?? ''));
$estimatedDuration = (int)($_POST['estimated_duration'] ?? 1);
$interventionAddress = trim((string)($_POST['intervention_address'] ?? ''));
$requestMessage = trim((string)($_POST['request_message'] ?? ''));

if ($token !== '') {
    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories', 'GET', null, $token);
    if (is_array($categoriesResponse) && !isset($categoriesResponse['error'])) {
        $categoryOptions = $categoriesResponse;
    } else {
        $errors[] = 'Erreur de chargement des categories.';
    }
}

if ($selectedCategoryId === '' && isset($_GET['category'])) {
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
    if ($token === '') {
        $errors[] = 'API indisponible.';
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
        $addressWithNote = $interventionAddress;
        if ($requestMessage !== '') {
            $addressWithNote .= ' | Note: ' . $requestMessage;
        }
        $addressWithNote = mb_substr($addressWithNote, 0, 255);

        $response = callAPI('http://localhost:8080/api/service-requests', 'POST', [
            'desired_date' => $desiredDate,
            'start_time' => $startTime,
            'estimated_duration' => $estimatedDuration,
            'intervention_address' => $addressWithNote,
            'status' => 'En attente',
            'id_user' => $userId,
            'id_service_category' => $selectedCategoryId,
        ], $token);

        if (is_array($response) && !isset($response['error'])) {
            header('Location: prestations-demandes.php?created=1');
            exit;
        } else {
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
