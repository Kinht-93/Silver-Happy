<?php
$seniorCurrent = 'profil';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Contact d’urgence</h1>
        <form class="senior-form" action="#" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="emergency_name">Nom du contact</label>
                    <input class="form-control" id="emergency_name" name="emergency_name" type="text">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="emergency_phone">Téléphone</label>
                    <input class="form-control" id="emergency_phone" name="emergency_phone" type="text">
                </div>
                <div class="col-12">
                    <label class="form-label" for="emergency_relation">Lien avec le contact</label>
                    <input class="form-control" id="emergency_relation" name="emergency_relation" type="text" placeholder="Ex: Fils, fille, voisin, ami...">
                </div>
            </div>
            <div class="senior-actions">
                <button class="btn btn-success" type="submit">Enregistrer</button>
                <a class="btn btn-outline-secondary" href="mon-profil.php">Retour</a>
            </div>
        </form>
    </div>
</section>

<?php include './include/footer.php'; ?>
