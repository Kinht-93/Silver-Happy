<?php
$seniorCurrent = 'prestations';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Prestations</h1>
        <p class="senior-subtitle">Accédez aux différentes rubriques de prestations.</p>

        <div class="senior-grid">
            <a class="senior-card" href="prestations-catalogue.php"><h2>Catalogue des prestations</h2><p>Consultez les services disponibles.</p></a>
            <a class="senior-card" href="prestations-demande.php"><h2>Demander une prestation</h2><p>Envoyez une nouvelle demande.</p></a>
            <a class="senior-card" href="prestations-demandes.php"><h2>Mes demandes</h2><p>Suivez vos demandes en cours.</p></a>
            <a class="senior-card" href="prestations-devis.php"><h2>Mes devis</h2><p>Consultez les devis reçus.</p></a>
            <a class="senior-card" href="prestations-realisees.php"><h2>Prestations réalisées</h2><p>Historique des interventions terminées.</p></a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
