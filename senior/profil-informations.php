<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'profil';
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$errors = [];
$success = '';

$form = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'postal_code' => '',
    'city' => '',
];

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $loadStmt = $pdo->prepare('SELECT first_name, last_name, email, phone, address, postal_code, city FROM users WHERE id_user = ? LIMIT 1');
        $loadStmt->execute([$userId]);
        $row = $loadStmt->fetch();
        if ($row) {
            foreach ($form as $key => $_) {
                $form[$key] = (string)($row[$key] ?? '');
            }
        }
    } catch (Throwable $e) {
        $errors[] = 'Impossible de charger vos informations.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if (!$pdo instanceof PDO) {
        $errors[] = 'Base de donnees indisponible.';
    }
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($form['first_name'] === '' || $form['last_name'] === '') {
        $errors[] = 'Le prenom et le nom sont obligatoires.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Un email valide est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare(
                'UPDATE users
                 SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, postal_code = ?, city = ?
                 WHERE id_user = ?'
            );
            $updateStmt->execute([
                $form['first_name'],
                $form['last_name'],
                $form['email'],
                $form['phone'],
                $form['address'],
                $form['postal_code'],
                $form['city'],
                $userId,
            ]);

            if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                $_SESSION['user']['first_name'] = $form['first_name'];
                $_SESSION['user']['last_name'] = $form['last_name'];
                $_SESSION['user']['email'] = $form['email'];
            }

            $success = 'Informations mises a jour avec succes.';
        } catch (Throwable $e) {
            $errors[] = 'Impossible d enregistrer vos informations.';
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Informations personnelles</h1>
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
                    <label class="form-label" for="first_name">Prénom</label>
                    <input class="form-control" id="first_name" name="first_name" type="text" value="<?= htmlspecialchars($form['first_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="last_name">Nom</label>
                    <input class="form-control" id="last_name" name="last_name" type="text" value="<?= htmlspecialchars($form['last_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($form['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Téléphone</label>
                    <input class="form-control" id="phone" name="phone" type="text" value="<?= htmlspecialchars($form['phone']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="address">Adresse</label>
                    <input class="form-control" id="address" name="address" type="text" value="<?= htmlspecialchars($form['address']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="postal_code">Code postal</label>
                    <input class="form-control" id="postal_code" name="postal_code" type="text" value="<?= htmlspecialchars($form['postal_code']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="city">Ville</label>
                    <input class="form-control" id="city" name="city" type="text" value="<?= htmlspecialchars($form['city']) ?>">
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
