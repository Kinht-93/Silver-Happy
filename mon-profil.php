<?php
include './include/header.php';
?>

<section class="account-section">
    <div class="account-card">
        <h1 class="account-title">Modifier mon profil</h1>

        <div class="profile-avatar" aria-hidden="true">
            <i class="bi bi-person-circle"></i>
        </div>

        <form action="#" method="post" class="account-form">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="profile_firstname" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="profile_firstname" name="profile_firstname">
                </div>
                <div class="col-md-4">
                    <label for="profile_lastname" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="profile_lastname" name="profile_lastname">
                </div>
                <div class="col-md-4">
                    <label for="profile_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="profile_email" name="profile_email">
                </div>

                <div class="col-md-4">
                    <label for="profile_phone" class="form-label">Téléphone</label>
                    <input type="text" class="form-control" id="profile_phone" name="profile_phone">
                </div>
                <div class="col-md-8">
                    <label for="profile_address" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="profile_address" name="profile_address">
                </div>

                <div class="col-md-4">
                    <label for="profile_city" class="form-label">Ville</label>
                    <input type="text" class="form-control" id="profile_city" name="profile_city">
                </div>
                <div class="col-md-4">
                    <label for="profile_zip" class="form-label">Code Postal</label>
                    <input type="text" class="form-control" id="profile_zip" name="profile_zip">
                </div>
            </div>

            <h2 class="account-subtitle">Sécurité</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="profile_password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" id="profile_password" name="profile_password">
                </div>
                <div class="col-md-4">
                    <label for="profile_password_confirm" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="profile_password_confirm" name="profile_password_confirm">
                </div>
            </div>

            <div class="account-actions">
                <a href="index.php" class="btn btn-warning">Annuler</a>
                <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</section>

<?php
include './include/footer.php';
?>
