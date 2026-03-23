<?php
require_once __DIR__ . '/../../db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$script = $_SERVER['SCRIPT_NAME'] ?? '';
$base_url = preg_replace('#/prestataire(/.*)?$#', '', $script);
$base_url = rtrim($base_url, '/');
$prestataire_url = $base_url . '/prestataire';
$current = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$userName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
if ($userName === '') {
    $userName = 'Prestataire';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Prestataire - Silver Happy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $base_url ?>/style-admin.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= $prestataire_url ?>/index.php">
            <img src="<?= $base_url ?>/img/logo.png" alt="Logo" class="admin-logo">
            <span class="brand-text ms-2">Silver Happy - Prestataire</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarProvider" aria-controls="navbarProvider" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarProvider">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($userName) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= $prestataire_url ?>/mon-profil.php">Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/logout.php">Deconnexion</a></li>
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
                        <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= $prestataire_url ?>/index.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'mon-profil.php' ? 'active' : '' ?>" href="<?= $prestataire_url ?>/mon-profil.php">
                            <i class="bi bi-person-vcard"></i> Mon profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'disponibilites.php' ? 'active' : '' ?>" href="<?= $prestataire_url ?>/disponibilites.php">
                            <i class="bi bi-calendar-check"></i> Disponibilites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'missions.php' ? 'active' : '' ?>" href="<?= $prestataire_url ?>/missions.php">
                            <i class="bi bi-briefcase"></i> Missions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'facturation.php' ? 'active' : '' ?>" href="<?= $prestataire_url ?>/facturation.php">
                            <i class="bi bi-cash-coin"></i> Facturation & paiements
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
