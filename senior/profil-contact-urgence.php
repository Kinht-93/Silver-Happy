<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'profil';
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$token = (string)($_SESSION['user']['token'] ?? '');

$errors = [];
$success = '';

$emergencyName = '';
$emergencyPhone = '';
$emergencyRelation = '';

if ($token !== '' && $userId !== '') {
    $userResponse = callAPI('http://localhost:8080/api/users/' . urlencode($userId), 'GET', null, $token);
    $settingsResponse = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/senior-settings', 'GET', null, $token);

    if (is_array($userResponse) && !isset($userResponse['error'])) {
        $emergencyName = (string)($userResponse['emergency_contact_name'] ?? '');
        $emergencyPhone = (string)($userResponse['emergency_contact_phone'] ?? '');
    }

    if (is_array($settingsResponse) && !isset($settingsResponse['error'])) {
        $emergencyRelation = (string)($settingsResponse['emergency_relation'] ?? '');
    }

    if ((isset($userResponse['error']) || !is_array($userResponse)) || (isset($settingsResponse['error']) || !is_array($settingsResponse))) {
        $errors[] = 'Impossible de charger le contact d urgence.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emergencyName = trim((string)($_POST['emergency_name'] ?? ''));
    $emergencyPhone = trim((string)($_POST['emergency_phone'] ?? ''));
    $emergencyRelation = trim((string)($_POST['emergency_relation'] ?? ''));

    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($token === '') {
        $errors[] = 'Connexion API indisponible.';
    }
    if ($emergencyName === '') {
        $errors[] = 'Le nom du contact est obligatoire.';
    }
    if ($emergencyPhone === '') {
        $errors[] = 'Le telephone du contact est obligatoire.';
    }

    if (empty($errors)) {
        $userUpdate = callAPI('http://localhost:8080/api/users/' . urlencode($userId), 'PATCH', [
            'emergency_contact_name' => $emergencyName,
            'emergency_contact_phone' => $emergencyPhone,
        ], $token);
        $settingsUpdate = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/senior-settings', 'PATCH', [
            'emergency_relation' => $emergencyRelation,
        ], $token);

        if (is_array($userUpdate) && !isset($userUpdate['error']) && is_array($settingsUpdate) && !isset($settingsUpdate['error'])) {
            $success = 'Contact d urgence mis a jour avec succes.';
        } else {
            $errors[] = $userUpdate['error'] ?? $settingsUpdate['error'] ?? 'Impossible d enregistrer le contact d urgence.';
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Contact d’urgence</h1>
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
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
                    <label class="form-label" for="emergency_name">Nom du contact</label>
                    <input class="form-control" id="emergency_name" name="emergency_name" type="text" value="<?= htmlspecialchars($emergencyName) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="emergency_phone">Téléphone</label>
                    <input class="form-control" id="emergency_phone" name="emergency_phone" type="text" value="<?= htmlspecialchars($emergencyPhone) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="emergency_relation">Lien avec le contact</label>
                    <input class="form-control" id="emergency_relation" name="emergency_relation" type="text" value="<?= htmlspecialchars($emergencyRelation) ?>" placeholder="Ex: Fils, fille, voisin, ami...">
                </div>
            </div>
            <div class="senior-actions">
                <button class="btn btn-success" type="submit">Enregistrer</button>
                <a class="btn btn-outline-secondary" href="mon-profil.php">Retour</a>
            </div>
        </form>
    </div>
</section>

<?php include './include/footer.php'; ?>
