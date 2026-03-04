<?php
$seniorCurrent = 'dashboard';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <div class="senior-dashboard-head">
            <div>
                <h1 class="senior-title">Tableau de bord</h1>
                <p class="senior-subtitle mb-0">Bienvenue dans votre espace personnel.</p>
            </div>
        </div>

        <div class="senior-dashboard-layout">
            <aside class="senior-quick-nav">
                <a class="senior-quick-link is-active" href="index.php"><i class="bi bi-grid-1x2-fill"></i> Tableau de bord</a>
                <a class="senior-quick-link" href="#"><i class="bi bi-calendar3"></i> Mon planning</a>
                <a class="senior-quick-link" href="#"><i class="bi bi-chat-dots-fill"></i> Messagerie</a>
                <a class="senior-quick-link" href="../mes-factures.php"><i class="bi bi-receipt"></i> Mes factures</a>
                <a class="senior-quick-link" href="mon-profil.php"><i class="bi bi-person-fill"></i> Mon profil</a>
                <a class="senior-quick-link is-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>

                <div class="senior-help-box">
                    <div class="senior-help-title">Besoin d’aide ?</div>
                    <p>Nos conseillers sont là pour vous aider</p>
                    <a href="tel:0123456789">01-23-45-67-89</a>
                </div>
            </aside>

            <div>
                <div class="senior-stat-row">
                    <div class="senior-stat-card senior-stat-primary">
                        <div class="senior-stat-label">Événements à venir</div>
                        <div class="senior-stat-value">0</div>
                    </div>
                    <div class="senior-stat-card senior-stat-success">
                        <div class="senior-stat-label">Prestations réalisées</div>
                        <div class="senior-stat-value">567</div>
                    </div>
                    <div class="senior-stat-card senior-stat-warning">
                        <div class="senior-stat-label">Demandes en cours</div>
                        <div class="senior-stat-value">23</div>
                    </div>
                    <div class="senior-stat-card senior-stat-danger">
                        <div class="senior-stat-label">Nouveaux messages</div>
                        <div class="senior-stat-value">5</div>
                    </div>
                </div>

                <div class="senior-main-grid">
                    <div class="senior-panel p-4">
                        <h3 class="senior-panel-title">Activité du mois</h3>
                        <div class="senior-chart-placeholder">Graphique d'activité (données en dur)</div>
                    </div>

                    <div class="senior-panel p-4">
                        <h3 class="senior-panel-title">Actualités de la communauté</h3>
                        <div class="mb-3">
                            <strong>Nouveau partenariat</strong>
                            <p class="small text-muted mb-0">Découvrez notre nouveau service de livraison de repas...</p>
                        </div>
                        <div class="mb-3">
                            <strong>Fête des voisins</strong>
                            <p class="small text-muted mb-0">Rejoignez-nous le 26 mai pour un moment convivial...</p>
                        </div>
                        <div>
                            <strong>Sortie culturelle</strong>
                            <p class="small text-muted mb-0">Inscrivez-vous pour la visite guidée du musée.</p>
                        </div>
                    </div>
                </div>

                <div class="senior-bottom-grid mt-3">
                    <div class="senior-panel p-4">
                        <h3 class="senior-panel-title">Événements à venir</h3>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-semibold">Journée portes ouvertes</div>
                                <small class="text-muted"><i class="bi bi-calendar"></i> 25/03/2026</small>
                            </div>
                            <span class="badge bg-info">Confirmé</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">Conférence bien vieillir</div>
                                <small class="text-muted"><i class="bi bi-calendar"></i> 10/04/2026</small>
                            </div>
                            <span class="badge bg-warning text-dark">Planification</span>
                        </div>
                    </div>

                    <div class="senior-panel p-4">
                        <h3 class="senior-panel-title">Tâches urgentes</h3>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="senior_task_1">
                            <label class="form-check-label" for="senior_task_1">Répondre aux 12 devis en attente</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="senior_task_2">
                            <label class="form-check-label" for="senior_task_2">Vérifier les nouvelles demandes de service</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="senior_task_3">
                            <label class="form-check-label" for="senior_task_3">Consulter la messagerie</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
