<?php
$seniorCurrent = 'profil';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Mon profil</h1>
        <p class="senior-subtitle">Choisissez la section à modifier.</p>

        <div class="senior-grid">
            <a class="senior-card" href="profil-informations.php">
                <h2>Informations personnelles</h2>
                <p>Nom, prénom, email, téléphone et adresse.</p>
            </a>
            <a class="senior-card" href="abonnements.php">
                <h2>Mon abonnement</h2>
                <p>Voir les formules et activer votre abonnement.</p>
            </a>
            <a class="senior-card" href="profil-preferences.php">
                <h2>Préférences / langue</h2>
                <p>Langue d’affichage et préférences générales.</p>
            </a>
            <a class="senior-card" href="profil-contact-urgence.php">
                <h2>Contact d’urgence</h2>
                <p>Personne à prévenir et informations de contact.</p>
            </a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
