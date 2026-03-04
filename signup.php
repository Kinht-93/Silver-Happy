<?php
session_start();
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

    if (empty($errors)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'email' => $email,
                    'password' => $password,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'birth_date' => $birthdate,
                    'role' => 'senior'
                ]),
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents('http://localhost:8080/api/signup', false, $context);

        if ($response === false) {
            $errors[] = 'Erreur de connexion avec le serveur.';
        } else {
            $data = json_decode($response, true);

            if ($data === null) {
                $errors[] = 'Erreur serveur: réponse invalide';
                error_log('Signup JSON Error: ' . $response);
            } elseif (isset($data['success']) && $data['success'] === true) {
                header('Location: login.php?signup=success');
                exit;
            } elseif (isset($data['error'])) {
                $errors[] = $data['error'];
            } elseif (isset($data['message'])) {
                $errors[] = $data['message'];
            } else {
                $errors[] = 'Erreur lors de la création du compte.';
            }
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
