<?php
$seniorCurrent = 'contact';
include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Nous Coordonnées</h1>
            <p class="senier-subtitle">Contactez notre équipe en quelques clics.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Contact</div>
    </div>

    <div class="senier-layout">
        <aside class="senier-sidebar-card">
            <h3>Nos Coordonnées</h3>
            <div class="senier-contact-item">
                <strong>Adresse</strong>
                <p>214 rue du Faubourg Saint Antoine<br>75012 Paris</p>
            </div>
            <div class="senier-contact-item">
                <strong>Téléphone</strong>
                <a href="tel:0123456789">01 23 45 67 89</a>
            </div>
            <div class="senier-contact-item mb-0">
                <strong>Email</strong>
                <a href="mailto:contact@silverhappy.fr">contact@silverhappy.fr</a>
            </div>
        </aside>

        <div class="senier-panel">
            <h3 class="senier-panel-title text-center">Rejoindre Silver Happy</h3>
            <p class="text-center text-muted small mb-3">Créer votre communauté de services</p>

            <form class="senier-form">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="contact_name">Votre nom *</label>
                        <input type="text" class="form-control" id="contact_name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_email">Votre Email *</label>
                        <input type="email" class="form-control" id="contact_email">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact_subject">Sujet</label>
                        <select class="form-select" id="contact_subject">
                            <option value="">Choisir un sujet</option>
                            <option>Information prestation</option>
                            <option>Aide compte</option>
                            <option>Partenariat</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact_message">Message *</label>
                        <textarea class="form-control" id="contact_message" rows="5"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn senier-send">Envoyer le message <i class="bi bi-send"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
