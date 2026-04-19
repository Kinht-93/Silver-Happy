<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

if (!function_exists('sh_ensure_senior_settings_table')) {
    function sh_ensure_senior_settings_table($pdo)
    {
        if (!$pdo instanceof PDO) {
            return;
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS senior_settings (
                id_user VARCHAR(255) PRIMARY KEY,
                language VARCHAR(10) NOT NULL DEFAULT 'fr',
                font_size VARCHAR(30) NOT NULL DEFAULT 'Normale',
                email_notifications BOOLEAN NOT NULL DEFAULT TRUE,
                emergency_relation VARCHAR(120) DEFAULT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_senior_settings_updated_at (updated_at),
                FOREIGN KEY (id_user) REFERENCES users(id_user)
            )"
        );
    }
}

$seniorCurrent = 'profil';
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$errors = [];
$success = '';

$language = 'fr';
$fontSize = 'Normale';
$emailNotifications = true;

if ($pdo instanceof PDO && $userId !== '') {
    try {
        sh_ensure_senior_settings_table($pdo);

        $loadStmt = $pdo->prepare('SELECT language, font_size, email_notifications FROM senior_settings WHERE id_user = ? LIMIT 1');
        $loadStmt->execute([$userId]);
        $row = $loadStmt->fetch();
        if ($row) {
            $language = in_array((string)$row['language'], ['fr', 'en', 'es', 'de', 'it'], true) ? (string)$row['language'] : 'fr';
            $fontSize = in_array((string)$row['font_size'], ['Normale', 'Grande', 'Tres grande'], true) ? (string)$row['font_size'] : 'Normale';
            $emailNotifications = (bool)($row['email_notifications'] ?? true);
        }
    } catch (Throwable $e) {
        $errors[] = 'Impossible de charger vos preferences.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = trim((string)($_POST['language'] ?? 'fr'));
    $fontSize = trim((string)($_POST['font_size'] ?? 'Normale'));
    $emailNotifications = isset($_POST['email_notifications']);

    if (!$pdo instanceof PDO) {
        $errors[] = 'Base de donnees indisponible.';
    }
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if (!in_array($language, ['fr', 'en', 'es', 'de', 'it'], true)) {
        $errors[] = 'Langue invalide.';
    }
    if (!in_array($fontSize, ['Normale', 'Grande', 'Tres grande'], true)) {
        $errors[] = 'Taille de texte invalide.';
    }

    if (empty($errors)) {
        try {
            sh_ensure_senior_settings_table($pdo);

            $updateStmt = $pdo->prepare(
                'INSERT INTO senior_settings (id_user, language, font_size, email_notifications, updated_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    language = VALUES(language),
                    font_size = VALUES(font_size),
                    email_notifications = VALUES(email_notifications),
                    updated_at = NOW()'
            );
            $updateStmt->execute([
                $userId,
                $language,
                $fontSize,
                $emailNotifications ? 1 : 0,
            ]);

            $success = 'Preferences mises a jour avec succes.';
        } catch (Throwable $e) {
            $errors[] = 'Impossible d enregistrer vos preferences.';
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
