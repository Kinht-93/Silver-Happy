<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once __DIR__ . '/role_redirect.php';
require_once __DIR__ . '/../active_user.php';

$basePath = isset($basePath) ? rtrim($basePath, '/') . '/' : '';
$isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']);
$userFirstName = $isLoggedIn ? (string)($_SESSION['user']['first_name'] ?? '') : '';
$roleHome = $isLoggedIn ? sh_get_role_home($_SESSION['user']['role'] ?? '') : 'index.php';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silver Happy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $basePath; ?>index.php">
            <img src="<?php echo $basePath; ?>img/logo.png" alt="Logo">
            <span class="brand-text ms-2">Silver Happy</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>index.php#prestations">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>index.php#evenements">Loisirs</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>index.php#temoignages">Conseils</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>index.php#presentation">Produits</a></li>
            </ul>
            <div id="google_translate_element" class="me-3"></div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-tutorial">
                    <i class="bi bi-question-circle"></i>
                    <span class="d-none d-md-inline">Guide</span>
                </button>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $basePath . $roleHome; ?>" class="btn btn-outline-primary">
                        <?php echo $userFirstName !== '' ? 'Bonjour ' . htmlspecialchars($userFirstName) : 'Espace senior'; ?>
                    </a>
                    <a href="<?php echo $basePath; ?>logout.php" class="btn btn-primary">Déconnexion</a>
                <?php else: ?>
                    <a href="<?php echo $basePath; ?>signup.php" class="btn btn-outline-primary">Inscription</a>
                    <a href="<?php echo $basePath; ?>login.php" class="btn btn-primary">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div id="tutorial-overlay" class="tutorial-overlay d-none">
    <div class="tutorial-backdrop"></div>
</div>

<div id="tutorial-panel" class="tutorial-panel shadow-lg d-none">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <h2 class="tutorial-title h5 mb-1" id="tutorial-title"></h2>
            <p class="tutorial-step-label small text-muted mb-0" id="tutorial-step-label"></p>
        </div>
        <button type="button" class="btn-close" aria-label="Fermer" id="tutorial-close"></button>
    </div>
    <p class="tutorial-text mb-3" id="tutorial-text"></p>
    <div class="d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-link btn-sm p-0" id="tutorial-skip">Ne plus afficher</button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="tutorial-prev">Précédent</button>
            <button type="button" class="btn btn-primary btn-sm" id="tutorial-next">Suivant</button>
        </div>
    </div>
</div>

<div class="container mt-4">
