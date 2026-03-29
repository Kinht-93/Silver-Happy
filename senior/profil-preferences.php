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

$language = 'fr';
$fontSize = 'Normale';
$emailNotifications = true;

if ($token !== '' && $userId !== '') {
    $settingsResponse = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/senior-settings', 'GET', null, $token);
    if (is_array($settingsResponse) && !isset($settingsResponse['error'])) {
        $language = in_array((string)($settingsResponse['language'] ?? 'fr'), ['fr', 'en', 'es', 'de', 'it'], true) ? (string)$settingsResponse['language'] : 'fr';
        $fontSize = in_array((string)($settingsResponse['font_size'] ?? 'Normale'), ['Normale', 'Grande', 'Tres grande'], true) ? (string)$settingsResponse['font_size'] : 'Normale';
        $emailNotifications = (bool)($settingsResponse['email_notifications'] ?? true);
    } else {
        $errors[] = 'Impossible de charger vos preferences.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = trim((string)($_POST['language'] ?? 'fr'));
    $fontSize = trim((string)($_POST['font_size'] ?? 'Normale'));
    $emailNotifications = isset($_POST['email_notifications']);

    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($token === '') {
        $errors[] = 'Connexion API indisponible.';
    }
    if (!in_array($language, ['fr', 'en', 'es', 'de', 'it'], true)) {
        $errors[] = 'Langue invalide.';
    }
    if (!in_array($fontSize, ['Normale', 'Grande', 'Tres grande'], true)) {
        $errors[] = 'Taille de texte invalide.';
    }

    if (empty($errors)) {
        $response = callAPI('http://localhost:8080/api/users/' . urlencode($userId) . '/senior-settings', 'PATCH', [
            'language' => $language,
            'font_size' => $fontSize,
            'email_notifications' => $emailNotifications,
        ], $token);

        if (is_array($response) && !isset($response['error'])) {
            $success = 'Preferences mises a jour avec succes.';
        } else {
            $errors[] = $response['error'] ?? 'Impossible d enregistrer vos preferences.';
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Préférences / langue</h1>
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
                    <label class="form-label" for="language">Langue</label>
                    <select class="form-select" id="language" name="language">
                        <option value="fr" <?= $language === 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="en" <?= $language === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= $language === 'es' ? 'selected' : '' ?>>Español</option>
                        <option value="de" <?= $language === 'de' ? 'selected' : '' ?>>Deutsch</option>
                        <option value="it" <?= $language === 'it' ? 'selected' : '' ?>>Italiano</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="font_size">Taille du texte</label>
                    <select class="form-select" id="font_size" name="font_size">
                        <option <?= $fontSize === 'Normale' ? 'selected' : '' ?>>Normale</option>
                        <option <?= $fontSize === 'Grande' ? 'selected' : '' ?>>Grande</option>
                        <option <?= $fontSize === 'Tres grande' ? 'selected' : '' ?>>Tres grande</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" id="email_notifications" name="email_notifications" type="checkbox" <?= $emailNotifications ? 'checked' : '' ?>>
                        <label class="form-check-label" for="email_notifications">Recevoir les notifications par email</label>
                    </div>
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
