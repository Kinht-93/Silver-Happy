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

$emergencyName = '';
$emergencyPhone = '';
$emergencyRelation = '';

if ($pdo instanceof PDO && $userId !== '') {
    try {
        sh_ensure_senior_settings_table($pdo);

        $loadStmt = $pdo->prepare('SELECT emergency_contact_name, emergency_contact_phone FROM users WHERE id_user = ? LIMIT 1');
        $loadStmt->execute([$userId]);
        $row = $loadStmt->fetch();
        if ($row) {
            $emergencyName = (string)($row['emergency_contact_name'] ?? '');
            $emergencyPhone = (string)($row['emergency_contact_phone'] ?? '');
        }

        $settingsStmt = $pdo->prepare('SELECT emergency_relation FROM senior_settings WHERE id_user = ? LIMIT 1');
        $settingsStmt->execute([$userId]);
        $settings = $settingsStmt->fetch();
        if ($settings) {
            $emergencyRelation = (string)($settings['emergency_relation'] ?? '');
        }
    } catch (Throwable $e) {
        $errors[] = 'Impossible de charger le contact d urgence.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emergencyName = trim((string)($_POST['emergency_name'] ?? ''));
    $emergencyPhone = trim((string)($_POST['emergency_phone'] ?? ''));
    $emergencyRelation = trim((string)($_POST['emergency_relation'] ?? ''));

    if (!$pdo instanceof PDO) {
        $errors[] = 'Base de donnees indisponible.';
    }
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($emergencyName === '') {
        $errors[] = 'Le nom du contact est obligatoire.';
    }
    if ($emergencyPhone === '') {
        $errors[] = 'Le telephone du contact est obligatoire.';
    }

    if (empty($errors)) {
        try {
            sh_ensure_senior_settings_table($pdo);

            $updateUserStmt = $pdo->prepare(
                'UPDATE users
                 SET emergency_contact_name = ?, emergency_contact_phone = ?
                 WHERE id_user = ?'
            );
            $updateUserStmt->execute([
                $emergencyName,
                $emergencyPhone,
                $userId,
            ]);

            $updateSettingsStmt = $pdo->prepare(
                'INSERT INTO senior_settings (id_user, emergency_relation, updated_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    emergency_relation = VALUES(emergency_relation),
                    updated_at = NOW()'
            );
            $updateSettingsStmt->execute([
                $userId,
                $emergencyRelation,
            ]);

            $success = 'Contact d urgence mis a jour avec succes.';
        } catch (Throwable $e) {
            $errors[] = 'Impossible d enregistrer le contact d urgence.';
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
