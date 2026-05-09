<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'contact';
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$token = (string)($_SESSION['user']['token'] ?? '');

$errors = [];
$success = '';
$contactName = trim((string)($_POST['contact_name'] ?? ''));
$contactEmail = trim((string)($_POST['contact_email'] ?? ''));
$contactSubject = trim((string)($_POST['contact_subject'] ?? ''));
$contactMessage = trim((string)($_POST['contact_message'] ?? ''));

if ($contactName === '' && isset($_SESSION['user'])) {
    $contactName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
}
if ($contactEmail === '' && isset($_SESSION['user']['email'])) {
    $contactEmail = (string)$_SESSION['user']['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($token === '') {
        $errors[] = 'Connexion API indisponible.';
    }
    if ($contactName === '') {
        $errors[] = 'Le nom est obligatoire.';
    }
    if ($contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Un email valide est obligatoire.';
    }
    if ($contactMessage === '') {
        $errors[] = 'Le message est obligatoire.';
    }

    if (empty($errors)) {
        $admins = callAPI('http://localhost:8080/api/users-summary?roles=admin', 'GET', null, $token);
        $adminId = '';
        if (is_array($admins) && !isset($admins['error']) && !empty($admins)) {
            $adminId = (string)($admins[0]['id_user'] ?? '');
        }

        if ($adminId === '') {
            $errors[] = 'Aucun administrateur disponible pour recevoir votre message.';
        } else {
            $payload = "Sujet: " . ($contactSubject !== '' ? $contactSubject : 'General')
                . "\nNom: " . $contactName
                . "\nEmail: " . $contactEmail
                . "\n\n" . $contactMessage;

            $response = callAPI('http://localhost:8080/api/messages', 'POST', [
                'content' => mb_substr($payload, 0, 5000),
                'receiver' => $adminId,
                'sender' => $userId,
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                $success = 'Votre message a bien ete envoye.';
                $contactSubject = '';
                $contactMessage = '';
            } else {
                $errors[] = $response['error'] ?? 'Impossible d envoyer le message pour le moment.';
            }
        }
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Nous Coordonnées</h1>
            <p class="senier-subtitle">Contactez notre équipe en quelques clics.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Contact</div>
    </div>

    <div class="senier-layout">
        <aside class="senier-sidebar-card">
            <h3>Nos Coordonnées</h3>
            <div class="senier-contact-item">
                <strong>Adresse</strong>
                <p>214 rue du Faubourg Saint Antoine<br>75012 Paris</p>
            </div>
            <div class="senier-contact-item">
                <strong>Téléphone</strong>
                <a href="tel:0123456789">01 23 45 67 89</a>
            </div>
            <div class="senier-contact-item mb-0">
                <strong>Email</strong>
                <a href="mailto:contact@silverhappy.fr">contact@silverhappy.fr</a>
            </div>
        </aside>

        <div class="senier-panel">
            <h3 class="senier-panel-title text-center">Rejoindre Silver Happy</h3>
            <p class="text-center text-muted small mb-3">Créer votre communauté de services</p>

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

            <form class="senier-form" method="post">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="contact_name">Votre nom *</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?= htmlspecialchars($contactName) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_email">Votre Email *</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($contactEmail) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact_subject">Sujet</label>
                        <select class="form-select" id="contact_subject" name="contact_subject">
                            <option value="">Choisir un sujet</option>
                            <option <?= $contactSubject === 'Information prestation' ? 'selected' : '' ?>>Information prestation</option>
                            <option <?= $contactSubject === 'Aide compte' ? 'selected' : '' ?>>Aide compte</option>
                            <option <?= $contactSubject === 'Partenariat' ? 'selected' : '' ?>>Partenariat</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact_message">Message *</label>
                        <textarea class="form-control" id="contact_message" name="contact_message" rows="5" required><?= htmlspecialchars($contactMessage) ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" class="btn senier-send">Envoyer le message <i class="bi bi-send"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
