<?php
include './include/header-admin.php';
?>

<div class="page-title">Logs & supervision</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tous-tab" data-bs-toggle="tab" data-bs-target="#tous" type="button" role="tab">Tous les logs</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="erreurs-tab" data-bs-toggle="tab" data-bs-target="#erreurs" type="button" role="tab">Erreurs</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="acces-tab" data-bs-toggle="tab" data-bs-target="#acces" type="button" role="tab">Accès</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="modif-tab" data-bs-toggle="tab" data-bs-target="#modif" type="button" role="tab">Modifications</button>
    </li>
</ul>

<div class="row mb-4">
    <div class="col text-end">
        <input type="date" class="form-control" style="max-width: 150px;">
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tous" role="tabpanel">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-label">Requêtes/jour</div>
                    <div class="stat-value">1,245</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-label">Taux succès</div>
                    <div class="stat-value">97,7%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-label">Erreurs 4xx</div>
                    <div class="stat-value">42</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-label">Erreurs 5xx</div>
                    <div class="stat-value">6</div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h5 class="mb-3">Historique des activités</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Détails</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>19/02/2026 15:32:11</td>
                            <td>admin@silverhappy.fr</td>
                            <td>Création de devis</td>
                            <td><span class="badge bg-info">Create</span></td>
                            <td>Devis #DV-2026-001</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 14:45:22</td>
                            <td>sophie.dubois@email.com</td>
                            <td>Connexion utilisateur</td>
                            <td><span class="badge bg-light text-dark">Login</span></td>
                            <td>IP: 192.168.1.45</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 13:20:44</td>
                            <td>content@silverhappy.fr</td>
                            <td>Modification d'article</td>
                            <td><span class="badge bg-warning">Update</span></td>
                            <td>Article #APP-001</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 12:15:33</td>
                            <td>marie.dupont@email.com</td>
                            <td>Erreur d'accès</td>
                            <td><span class="badge bg-danger">Error</span></td>
                            <td>Accès refusé à la ressource</td>
                            <td><span class="badge bg-danger">Échoué</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 11:08:15</td>
                            <td>admin@silverhappy.fr</td>
                            <td>Suppression de contenu</td>
                            <td><span class="badge bg-danger">Delete</span></td>
                            <td>Conseil #CONS-1234</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 10:42:09</td>
                            <td>finance@silverhappy.fr</td>
                            <td>Export de rapport</td>
                            <td><span class="badge bg-light text-dark">Export</span></td>
                            <td>Rapport facturation février</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 09:19:47</td>
                            <td>système</td>
                            <td>Sauvegarde programmée</td>
                            <td><span class="badge bg-light text-dark">System</span></td>
                            <td>Sauvegarde complète base de données</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>18/02/2026 23:55:32</td>
                            <td>jean.martin@email.com</td>
                            <td>Déconnexion</td>
                            <td><span class="badge bg-light text-dark">Logout</span></td>
                            <td>Session fermée normalement</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="erreurs" role="tabpanel">
        <div class="admin-card">
            <h5 class="mb-3">Logs - Erreurs</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Détails</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>19/02/2026 12:15:33</td>
                            <td>marie.dupont@email.com</td>
                            <td>Erreur d'accès</td>
                            <td><span class="badge bg-danger">Error</span></td>
                            <td>Accès refusé à la ressource</td>
                            <td><span class="badge bg-danger">Échoué</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="acces" role="tabpanel">
        <div class="admin-card">
            <h5 class="mb-3">Logs - Accès</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Détails</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>19/02/2026 14:45:22</td>
                            <td>sophie.dubois@email.com</td>
                            <td>Connexion utilisateur</td>
                            <td><span class="badge bg-light text-dark">Login</span></td>
                            <td>IP: 192.168.1.45</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>18/02/2026 23:55:32</td>
                            <td>jean.martin@email.com</td>
                            <td>Déconnexion</td>
                            <td><span class="badge bg-light text-dark">Logout</span></td>
                            <td>Session fermée normalement</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="modif" role="tabpanel">
        <div class="admin-card">
            <h5 class="mb-3">Logs - Modifications</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Détails</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>19/02/2026 15:32:11</td>
                            <td>admin@silverhappy.fr</td>
                            <td>Création de devis</td>
                            <td><span class="badge bg-info">Create</span></td>
                            <td>Devis #DV-2026-001</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 13:20:44</td>
                            <td>content@silverhappy.fr</td>
                            <td>Modification d'article</td>
                            <td><span class="badge bg-warning">Update</span></td>
                            <td>Article #APP-001</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                        <tr>
                            <td>19/02/2026 11:08:15</td>
                            <td>admin@silverhappy.fr</td>
                            <td>Suppression de contenu</td>
                            <td><span class="badge bg-danger">Delete</span></td>
                            <td>Conseil #CONS-1234</td>
                            <td><span class="badge bg-success">Succès</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
