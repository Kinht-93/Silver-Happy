<?php
session_start();
include_once 'db.php';
include_once './include/role_redirect.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . sh_get_role_home($_SESSION['user']['role'] ?? ''));
    exit;
}

$errors = [];

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    $termsAccepted = isset($_POST['terms']);

    if ($firstName === '') {
        $errors[] = 'Le prénom est obligatoire.';
    }

    if ($lastName === '') {
        $errors[] = 'Le nom est obligatoire.';
    }

    if ($birthdate === '') {
        $errors[] = 'La date de naissance est obligatoire.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Veuillez saisir une adresse email valide.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'La confirmation du mot de passe ne correspond pas.';
    }

    if (!$termsAccepted) {
        $errors[] = 'Vous devez accepter les conditions d\'utilisation.';
    }

    if (!$pdo instanceof PDO) {
        $errors[] = 'La base de données est indisponible pour le moment.';
    }

    if (empty($errors) && $pdo instanceof PDO) {
        try {
            $checkStmt = $pdo->prepare('SELECT id_user FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch()) {
                $errors[] = 'Un compte existe déjà avec cette adresse email.';
            } else {
                try {
                    $userId = 'usr_' . bin2hex(random_bytes(16));
                } catch (Exception $e) {
                    $userId = 'usr_' . uniqid('', true);
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $createdAt = date('Y-m-d H:i:s');

                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (
                        id_user,
                        email,
                        password,
                        role,
                        last_name,
                        first_name,
                        birth_date,
                        created_at
                    ) VALUES (
                        :id_user,
                        :email,
                        :password,
                        :role,
                        :last_name,
                        :first_name,
                        :birth_date,
                        :created_at
                    )'
                );

                $insertStmt->execute([
                    'id_user' => $userId,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'role' => 'senior',
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'birth_date' => $birthdate,
                    'created_at' => $createdAt,
                ]);

                header('Location: login.php?signup=success');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Une erreur est survenue lors de la création du compte.';
        }
    }
}

include './include/header.php';
?>

<section class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title mb-1">Rejoindre Silver Happy</h1>
        <p class="auth-subtitle mb-4">Créez votre compte pour accéder à nos services.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="auth-form">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="birthdate" class="form-label">Date de naissance</label>
                <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Adresse email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-6">
                    <label for="password_confirm" class="form-label">Confirmer</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    J'accepte les conditions d'utilisation
                </label>
            </div>

            <button type="submit" class="btn btn-success w-100 mb-3 auth-submit">Créer mon compte</button>

            <div class="auth-links text-center">
                <a href="signup_presta.php" class="d-block mb-1">Devenir partenaire</a>
                <a href="login.php" class="auth-link-secondary">Déjà inscrit ?</a>
            </div>
        </form>
    </div>
</section>

<?php
include './include/footer.php';
?>
