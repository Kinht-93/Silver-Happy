<?php
include './include/header-admin.php';
?>

<div class="page-title">Tableau de bord</div>

<div class="row mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-label">Utilisateurs actifs</div>
            <div class="stat-value">1,234</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-label">Prestations réalisées</div>
            <div class="stat-value">567</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-label">Devis en attente</div>
            <div class="stat-value">23</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card danger">
            <div class="stat-label">Problèmes signalés</div>
            <div class="stat-value">5</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Activité du mois</h3>
            <div style="height: 300px; background: linear-gradient(180deg, rgba(40, 90, 255, 0.1) 0%, rgba(79, 70, 229, 0.05) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <p class="text-muted">Graphique d'activité (données en dur)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Dernières transactions</h3>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Paiement adhésion</div>
                            <small class="text-muted">Marie Dupont</small>
                        </div>
                        <span class="badge bg-success">+50€</span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Prestation payée</div>
                            <small class="text-muted">Jean Martin</small>
                        </div>
                        <span class="badge bg-success">+75€</span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Achat produit</div>
                            <small class="text-muted">Fatima Ahmed</small>
                        </div>
                        <span class="badge bg-success">+25€</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Événements à venir</h3>
            <div class="list-group list-group-flush">
                <div class="list-group-item px-0 py-3 border-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Journée portes ouvertes</div>
                            <small class="text-muted"><i class="bi bi-calendar"></i> 25/03/2026</small>
                        </div>
                        <span class="badge bg-info">Confirmé</span>
                    </div>
                </div>
                <div class="list-group-item px-0 py-3 border-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Conférence bien vieillir</div>
                            <small class="text-muted"><i class="bi bi-calendar"></i> 10/04/2026</small>
                        </div>
                        <span class="badge bg-warning">Planification</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h3 class="h5 mb-4">Tâches urgentes</h3>
            <div class="list-group list-group-flush">
                <div class="list-group-item px-0 py-3 border-0">
                    <div class="d-flex gap-3">
                        <input type="checkbox" class="form-check-input" id="task1">
                        <label for="task1" class="flex-grow-1">Valider 5 demandes de prestataires en attente</label>
                    </div>
                </div>
                <div class="list-group-item px-0 py-3 border-0">
                    <div class="d-flex gap-3">
                        <input type="checkbox" class="form-check-input" id="task2">
                        <label for="task2" class="flex-grow-1">Répondre aux 12 devis en attente</label>
                    </div>
                </div>
                <div class="list-group-item px-0 py-3 border-0">
                    <div class="d-flex gap-3">
                        <input type="checkbox" class="form-check-input" id="task3">
                        <label for="task3" class="flex-grow-1">Publier 3 nouveaux conseils</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
