<?php
$seniorCurrent = 'evenements';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Événements</h1>
        <p class="senior-subtitle">Consultez les événements et suivez vos participations.</p>

        <div class="senior-grid">
            <a class="senior-card" href="evenements-liste.php"><h2>Liste des événements</h2><p>Événements disponibles à venir.</p></a>
            <a class="senior-card" href="evenements-inscriptions.php"><h2>Mes inscriptions</h2><p>Événements auxquels vous êtes inscrit.</p></a>
            <a class="senior-card" href="evenements-historique.php"><h2>Historique</h2><p>Vos participations passées.</p></a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
