<?php
$seniorCurrent = 'dashboard';
include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Tableau de bord</h1>
            <p class="senier-subtitle">Bienvenue dans votre espace personnel.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Tableau de bord</div>
    </div>

    <div>
            <div class="senier-home-kpis">
                <div class="senier-home-kpi">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <strong>0</strong>
                    <span>Événements à venir</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-clock-fill text-warning"></i>
                    <strong>2</strong>
                    <span>Demandes en cours</span>
                </div>
                <div class="senier-home-kpi">
                    <i class="bi bi-envelope-fill text-success"></i>
                    <strong>1</strong>
                    <span>Nouveau message</span>
                </div>
            </div>

            <div class="senier-home-alert mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Vous n'êtes inscrit à aucun événement pour le moment
                    <a href="planning.php">Découvrir l'agenda</a>
                </div>
            </div>

            <div class="senier-home-grid">
                <a class="senier-home-card" href="planning.php">
                    <h3><i class="bi bi-calendar3"></i> Planning</h3>
                    <p>Consultez votre agenda et vos prochains rendez-vous.</p>
                </a>
                <a class="senier-home-card" href="prestation.php">
                    <h3><i class="bi bi-briefcase"></i> Prestations</h3>
                    <p>Accédez rapidement aux services disponibles.</p>
                </a>
                <a class="senier-home-card" href="messagerie.php">
                    <h3><i class="bi bi-chat-dots"></i> Messagerie</h3>
                    <p>Échangez avec les prestataires et l'équipe.</p>
                </a>
                <a class="senier-home-card" href="mon-profil.php">
                    <h3><i class="bi bi-person-circle"></i> Mon profil</h3>
                    <p>Mettez à jour vos informations personnelles.</p>
                </a>
                <a class="senier-home-card" href="../mes-factures.php">
                    <h3><i class="bi bi-receipt"></i> Mes factures</h3>
                    <p>Retrouvez l'historique de vos paiements.</p>
                </a>
                <a class="senier-home-card" href="contact.php">
                    <h3><i class="bi bi-headset"></i> Contact</h3>
                    <p>Besoin d'aide ? Notre équipe reste disponible.</p>
                </a>
            </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
