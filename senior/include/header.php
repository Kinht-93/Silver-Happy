<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once __DIR__ . '/../../include/role_redirect.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$userRole = (string)($_SESSION['user']['role'] ?? '');
if (strtolower($userRole) !== 'senior') {
    header('Location: ../' . sh_get_role_home($userRole));
    exit;
}

$currentPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
if (!isset($seniorCurrent) || $seniorCurrent === '') {
    $sectionMap = [
        'index.php' => 'dashboard',
        'mon-profil.php' => 'profil',
        'profil-informations.php' => 'profil',
        'profil-preferences.php' => 'profil',
        'profil-contact-urgence.php' => 'profil',
        'contact.php' => 'contact',
        'planning.php' => 'planning',
        'prestation.php' => 'prestation',
        'messagerie.php' => 'messagerie',
        'prestations.php' => 'prestations',
        'prestations-catalogue.php' => 'prestations',
        'prestations-demande.php' => 'prestations',
        'prestations-demandes.php' => 'prestations',
        'prestations-devis.php' => 'prestations',
        'prestations-realisees.php' => 'prestations',
        'evenements.php' => 'evenements',
        'evenements-liste.php' => 'evenements',
        'evenements-inscriptions.php' => 'evenements',
        'evenements-historique.php' => 'evenements',
    ];

    $seniorCurrent = $sectionMap[$currentPage] ?? 'dashboard';
}

$firstName = (string)($_SESSION['user']['first_name'] ?? '');
$menuItems = [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => 'index.php'],
    ['key' => 'planning', 'label' => 'Planning', 'href' => 'planning.php'],
    ['key' => 'prestation', 'label' => 'Prestation', 'href' => 'prestation.php'],
    ['key' => 'messagerie', 'label' => 'Messagerie', 'href' => 'messagerie.php'],
    ['key' => 'contact', 'label' => 'Contact', 'href' => 'contact.php'],
    ['key' => 'profil', 'label' => 'Mon profil', 'href' => 'mon-profil.php'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Senior - Silver Happy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../senier.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light senior-main-header">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../img/logo.png" alt="Logo">
            <span class="brand-text ms-2">Silver Happy</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#seniorNavbar" aria-controls="seniorNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="seniorNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 senior-main-nav">
                <?php foreach ($menuItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $seniorCurrent === $item['key'] ? 'active' : ''; ?>" href="../senior/<?php echo $item['href']; ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <a href="<?php echo $basePath . $roleHome; ?>" class="btn btn-outline-primary">
                    <?php echo $userFirstName !== '' ? 'Bonjour ' . htmlspecialchars($firstName) : 'Espace senior'; ?>
                </a>
                <a href="<?php echo $basePath; ?>logout.php" class="btn btn-primary">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>

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
    <div class="senier-global-shell">
        <?php include __DIR__ . '/../_menu.php'; ?>
        <main class="senier-global-main">
