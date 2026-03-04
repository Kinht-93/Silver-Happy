<?php
$seniorCurrent = 'profil';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Préférences / langue</h1>
        <form class="senior-form" action="#" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="language">Langue</label>
                    <select class="form-select" id="language" name="language">
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                        <option value="es">Español</option>
                        <option value="de">Deutsch</option>
                        <option value="it">Italiano</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="font_size">Taille du texte</label>
                    <select class="form-select" id="font_size" name="font_size">
                        <option>Normale</option>
                        <option>Grande</option>
                        <option>Très grande</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" id="email_notifications" name="email_notifications" type="checkbox" checked>
                        <label class="form-check-label" for="email_notifications">Recevoir les notifications par email</label>
                    </div>
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
