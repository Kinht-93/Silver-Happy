<?php
$script = $_SERVER['SCRIPT_NAME'];

$base_url = preg_replace('#/admin(/.*)?$#', '', $script);
$base_url = rtrim($base_url, '/');

$admin_url = $base_url . '/admin';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Silver Happy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="<?= $base_url ?>/style-admin.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= $admin_url ?>/index.php">
            <img src="<?= $base_url ?>/img/logo.png" alt="Logo" class="admin-logo">
            <span class="brand-text ms-2">Silver Happy - Admin</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin"
                aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/logout.php">Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?= $admin_url ?>/index.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-utilisateurs/">
                            <i class="bi bi-people"></i> Gestion utilisateurs
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-prestations/">
                            <i class="bi bi-briefcase"></i> Gestion prestations
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-devis.php">
                            <i class="bi bi-file-earmark-text"></i> Gestion devis
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-evenements.php">
                            <i class="bi bi-calendar3"></i> Gestion événements
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-abonnements.php">
                            <i class="bi bi-credit-card"></i> Gestion abonnements
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-boutique/">
                            <i class="bi bi-shop"></i> Gestion boutique
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/gestion-contenus/">
                            <i class="bi bi-file-earmark-richtext"></i> Gestion contenus
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/facturation-paiements.php">
                            <i class="bi bi-cash-coin"></i> Facturation & paiements
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/notifications.php">
                            <i class="bi bi-bell"></i> Notifications système
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/logs.php">
                            <i class="bi bi-file-text"></i> Logs & supervision
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $admin_url ?>/parametres.php">
                            <i class="bi bi-gear"></i> Paramètres plateforme
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">