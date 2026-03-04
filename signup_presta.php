<?php
session_start();
include_once 'db.php';
include_once './include/role_redirect.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . sh_get_role_home($_SESSION['user']['role'] ?? ''));
    exit;
}

$errors = [];

$lastName = trim($_POST['last_name'] ?? '');
$firstName = trim($_POST['first_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$companyName = trim($_POST['company_name'] ?? '');
$siret = preg_replace('/\D+/', '', (string)($_POST['siret'] ?? ''));
$iban = trim($_POST['iban'] ?? '');
$zone = trim($_POST['zone'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($lastName === '') {
        $errors[] = 'Le nom est obligatoire.';
    }

    if ($firstName === '') {
        $errors[] = 'Le prénom est obligatoire.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Veuillez saisir une adresse email valide.';
    }

    if ($companyName === '') {
        $errors[] = 'La raison sociale est obligatoire.';
    }

    if ($siret === '' || strlen($siret) !== 14) {
        $errors[] = 'Le numéro SIRET doit contenir 14 chiffres.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'La confirmation du mot de passe ne correspond pas.';
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
            }
        } catch (Exception $e) {
            $errors[] = 'Impossible de vérifier cette adresse email pour le moment.';
        }
    }

    if (empty($errors) && $pdo instanceof PDO) {
        try {
            $pdo->beginTransaction();

            try {
                $userId = 'usr_' . bin2hex(random_bytes(16));
                $providerId = 'prv_' . bin2hex(random_bytes(16));
            } catch (Exception $e) {
                $userId = 'usr_' . uniqid('', true);
                $providerId = 'prv_' . uniqid('', true);
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $createdAt = date('Y-m-d H:i:s');

            $insertUserStmt = $pdo->prepare(
                'INSERT INTO users (
                    id_user,
                    email,
                    password,
                    role,
                    last_name,
                    first_name,
                    phone,
                    created_at
                ) VALUES (
                    :id_user,
                    :email,
                    :password,
                    :role,
                    :last_name,
                    :first_name,
                    :phone,
                    :created_at
                )'
            );

            $insertUserStmt->execute([
                'id_user' => $userId,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'prestataire',
                'last_name' => $lastName,
                'first_name' => $firstName,
                'phone' => $phone !== '' ? $phone : null,
                'created_at' => $createdAt,
            ]);

            $providersTableExists = false;
            $isProviderTableExists = false;

            $providersTableStmt = $pdo->query("SHOW TABLES LIKE 'providers'");
            if ($providersTableStmt && $providersTableStmt->fetchColumn()) {
                $providersTableExists = true;
            }

            $isProviderTableStmt = $pdo->query("SHOW TABLES LIKE 'is_provider'");
            if ($isProviderTableStmt && $isProviderTableStmt->fetchColumn()) {
                $isProviderTableExists = true;
            }

            if ($providersTableExists) {
                $checkProviderStmt = $pdo->prepare('SELECT id_provider FROM providers WHERE siret_number = :siret OR company_name = :company LIMIT 1');
                $checkProviderStmt->execute([
                    'siret' => $siret,
                    'company' => $companyName,
                ]);

                if ($checkProviderStmt->fetch()) {
                    throw new RuntimeException('Un prestataire existe déjà avec ce SIRET ou cette raison sociale.');
                }

                $insertProviderStmt = $pdo->prepare(
                    'INSERT INTO providers (
                        id_provider,
                        siret_number,
                        company_name,
                        validation_status,
                        average_rating,
                        commission_rate
                    ) VALUES (
                        :id_provider,
                        :siret_number,
                        :company_name,
                        :validation_status,
                        :average_rating,
                        :commission_rate
                    )'
                );

                $insertProviderStmt->execute([
                    'id_provider' => $providerId,
                    'siret_number' => $siret,
                    'company_name' => $companyName,
                    'validation_status' => 'en_attente',
                    'average_rating' => null,
                    'commission_rate' => null,
                ]);

                if ($isProviderTableExists) {
                    $insertLinkStmt = $pdo->prepare('INSERT INTO is_provider (id_user, id_provider) VALUES (:id_user, :id_provider)');
                    $insertLinkStmt->execute([
                        'id_user' => $userId,
                        'id_provider' => $providerId,
                    ]);
                }
            }

            $pdo->commit();
            header('Location: login.php?signup=success');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                $errors[] = 'Une erreur est survenue lors de la création du compte prestataire.';
            }
        }
    }
}

include './include/header.php';
?>

<section class="presta-signup-wrapper">
    <div class="presta-signup-card">
        <h1 class="presta-signup-title">Formulaire de candidature</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="presta-signup-form">
            <h2 class="presta-signup-section"><i class="bi bi-person-fill"></i> Informations personelles</h2>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="last_name" placeholder="Nom" value="<?php echo htmlspecialchars($lastName); ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="first_name" placeholder="Prénom" value="<?php echo htmlspecialchars($firstName); ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="email" class="form-control" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="phone" placeholder="Téléphone" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
            </div>

            <h2 class="presta-signup-section"><i class="bi bi-building"></i> Informations professionnelles</h2>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <input type="text" class="form-control" name="company_name" placeholder="Raison sociale" value="<?php echo htmlspecialchars($companyName); ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="siret" placeholder="Numéro SIRET" value="<?php echo htmlspecialchars($siret); ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="iban" placeholder="IBAN" value="<?php echo htmlspecialchars($iban); ?>">
                </div>
                <div class="col-12">
                    <input type="text" class="form-control" name="zone" placeholder="Zone d'intervention" value="<?php echo htmlspecialchars($zone); ?>">
                </div>
                <div class="col-12">
                    <textarea class="form-control" name="description" rows="4" placeholder="Description de votre activité"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <input type="password" class="form-control" name="password" placeholder="Mot de passe" required>
                </div>
                <div class="col-md-6">
                    <input type="password" class="form-control" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success w-100 mb-3 auth-submit">Envoyer ma candidature</button>
            </div>
        </form>
    </div>
</section>

<?php include './include/footer.php'; ?>
