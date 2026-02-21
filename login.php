<?php
include './include/header.php';
?>

<section class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title mb-1">Connexion</h1>
        <p class="auth-subtitle mb-4">Accédez à votre espace Silver Happy.</p>

        <form action="#" method="post" class="auth-form">
            <div class="mb-3">
                <label for="login_email" class="form-label">Adresse email</label>
                <input type="email" class="form-control" id="login_email" name="login_email" required>
            </div>

            <div class="mb-3">
                <label for="login_password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="login_password" name="login_password" required>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <a href="#" class="auth-link-secondary small">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn btn-success w-100 mb-3 auth-submit">Se connecter</button>

            <div class="auth-links text-center">
                <span class="d-block mb-1">Pas encore de compte ?</span>
                <a href="signup.php" class="auth-link-secondary">Créer un compte</a>
            </div>
        </form>
    </div>
</section>

<?php
include './include/footer.php';
?>
