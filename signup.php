<?php
include './include/header.php';
?>

<section class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title mb-1">Rejoindre Silver Happy</h1>
        <p class="auth-subtitle mb-4">Créez votre compte pour accéder à nos services.</p>

        <form action="#" method="post" class="auth-form">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="birthdate" class="form-label">Date de naissance</label>
                <input type="date" class="form-control" id="birthdate" name="birthdate" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Adresse email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-6">
                    <label for="password_confirm" class="form-label">Confirmer</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    J'accepte les conditions d'utilisation
                </label>
            </div>

            <button type="submit" class="btn btn-success w-100 mb-3 auth-submit">Créer mon compte</button>

            <div class="auth-links text-center">
                <a href="#" class="d-block mb-1">Devenir partenaire</a>
                <a href="login.php" class="auth-link-secondary">Déjà inscrit ?</a>
            </div>
        </form>
    </div>
</section>

<?php
include './include/footer.php';
?>
