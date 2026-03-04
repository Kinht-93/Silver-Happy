<?php
$seniorCurrent = 'prestation';
include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Prestations</h1>
            <p class="senier-subtitle">Liste des besoins adaptés à chaque résident.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Prestations</div>
    </div>

    <div class="senier-pills">
        <span class="senier-pill">Toutes</span>
        <span class="senier-pill">Services</span>
        <span class="senier-pill">Loisirs</span>
        <span class="senier-pill">Conseils</span>
    </div>

    <div class="senier-prestation-layout">
        <aside class="senier-categories">
            <h4>Catégories</h4>
            <a href="#">Toutes les prestations</a>
            <a href="#">Ménage</a>
            <a href="#">Assistance</a>
            <a href="#">Transport</a>
            <a href="#">Informatique</a>
            <a href="#">Santé</a>
            <a href="#">Courses</a>
            <a href="#">Animation</a>
            <a href="#">Accompagnement</a>
        </aside>

        <div class="senier-prestation-grid">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <article class="senier-prestation-card">
                    <h5>Nom prestation</h5>
                    <p>Service personnalisé pour simplifier votre quotidien.</p>
                    <button type="button" class="btn btn-outline-secondary w-100">Prendre un rendez-vous</button>
                </article>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
