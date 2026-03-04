<?php
session_start();
include_once 'db.php';
include_once './include/role_redirect.php';

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    header('Location: ' . sh_get_role_home($_SESSION['user_role'] ?? ''));
    exit;
}

$errors = [];
$email = trim($_POST['login_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['login_password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_errors'] = ['Email et mot de passe sont requis'];
        header('Location: login.php');
        exit();
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'email' => $email,
                'password' => $password
            ])
        ]
    ]);

    $response = @file_get_contents('http://localhost:8080/api/login', false, $context);

    if ($response === false) {
        $_SESSION['login_errors'] = ['Email ou mot de passe incorrect'];
        header('Location: login.php');
        exit();
    }

    $data = json_decode($response, true);

    if ($data === null) {
        $_SESSION['login_errors'] = ['Erreur serveur: réponse invalide'];
        error_log('Login JSON Error: ' . $response);
        header('Location: login.php');
        exit();
    }

    if (isset($data['token']) && !empty($data['token'])) {
        $_SESSION['user'] = [
            'token' => $data['token'],
            'id_user' => $data['user']['id_user'],
            'email' => $data['user']['email'],
            'role' => $data['user']['role'],
            'first_name' => $data['user']['first_name'],
            'last_name' => $data['user']['last_name'],
        ];
        header('Location: ' . sh_get_role_home($data['user']['role'] ?? ''));
        exit();
    } else {
        $error = $data['error'] ?? 'Erreur de connexion inconnue';
        $_SESSION['login_errors'] = [$error];
        $_SESSION['form_data'] = ['login_email' => $email];
        header('Location: login.php');
        exit();
    }
}

$showSignupSuccess = isset($_GET['signup']) && $_GET['signup'] === 'success';

$errors = $_SESSION['login_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
$email = $formData['login_email'] ?? $email;

unset($_SESSION['login_errors'], $_SESSION['form_data']);

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
