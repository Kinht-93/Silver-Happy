<?php
$seniorCurrent = 'profil';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Informations personnelles</h1>
        <form class="senior-form" action="#" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="first_name">Prénom</label>
                    <input class="form-control" id="first_name" name="first_name" type="text">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="last_name">Nom</label>
                    <input class="form-control" id="last_name" name="last_name" type="text">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Téléphone</label>
                    <input class="form-control" id="phone" name="phone" type="text">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="address">Adresse</label>
                    <input class="form-control" id="address" name="address" type="text">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="postal_code">Code postal</label>
                    <input class="form-control" id="postal_code" name="postal_code" type="text">
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
