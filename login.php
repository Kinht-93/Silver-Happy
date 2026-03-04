<?php
session_start();
include_once 'db.php';
include_once './include/role_redirect.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . sh_get_role_home($_SESSION['user']['role'] ?? ''));
    exit;
}

$errors = [];
$email = trim($_POST['login_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['login_password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Veuillez saisir une adresse email valide.';
    }

    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    }

    if (!$pdo instanceof PDO) {
        $errors[] = 'La base de données est indisponible pour le moment.';
    }

    if (empty($errors) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id_user, email, password, role, first_name, last_name, active
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Email ou mot de passe incorrect.';
            } elseif ((int)$user['active'] !== 1) {
                $errors[] = 'Votre compte est désactivé.';
            } else {
                $storedPassword = (string)$user['password'];
                $isPasswordValid = password_verify($password, $storedPassword) || $password === $storedPassword;

                if (!$isPasswordValid) {
                    $errors[] = 'Email ou mot de passe incorrect.';
                } else {
                    if ($password === $storedPassword || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id_user = :id_user');
                        $updateStmt->execute([
                            'password' => $newHash,
                            'id_user' => $user['id_user'],
                        ]);
                    }

                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id_user' => $user['id_user'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                    ];

                    header('Location: ' . sh_get_role_home($user['role'] ?? ''));
                    exit;
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Une erreur est survenue lors de la connexion.';
        }
    }
}

$showSignupSuccess = isset($_GET['signup']) && $_GET['signup'] === 'success';

include './include/header.php';
?>

<section class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title mb-1">Connexion</h1>
        <p class="auth-subtitle mb-4">Accédez à votre espace Silver Happy.</p>

        <?php if ($showSignupSuccess): ?>
            <div class="alert alert-success" role="alert">
                Compte créé avec succès. Vous pouvez maintenant vous connecter.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="auth-form">
            <div class="mb-3">
                <label for="login_email" class="form-label">Adresse email</label>
                <input type="email" class="form-control" id="login_email" name="login_email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="mb-3">
                <label for="login_password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="login_password" name="login_password" required>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <a href="#" class="auth-link-secondary small">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn btn-success w-100 mb-3 auth-submit">Se connecter</button>

            <div class="auth-links text-center">
                <span class="d-block mb-1">Pas encore de compte ?</span>
                <a href="signup.php" class="auth-link-secondary">Créer un compte</a>
            </div>
        </form>
    </div>
</section>

<?php
include './include/footer.php';
?>
