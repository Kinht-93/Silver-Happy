<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des Administrateurs</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-primary active">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddAdmin">+ Ajouter un administrateur</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Permissions</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Directeur Admin</td>
                    <td>admin@silverhappy.fr</td>
                    <td><span class="badge bg-danger">Super Admin</span></td>
                    <td>Tous les droits</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>aujourd'hui à 09:30</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Responsable Contenus</td>
                    <td>content@silverhappy.fr</td>
                    <td><span class="badge bg-warning">Manager</span></td>
                    <td>Gestion contenus</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>hier à 15:45</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Responsable Facturation</td>
                    <td>finance@silverhappy.fr</td>
                    <td><span class="badge bg-warning">Manager</span></td>
                    <td>Facturation & paiements</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>19/02/2026 à 11:20</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Modérateur Support</td>
                    <td>moderation@silverhappy.fr</td>
                    <td><span class="badge bg-info">Modérateur</span></td>
                    <td>Modération, Notifications</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>18/02/2026 à 14:15</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="modalAddAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un administrateur</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="adminName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="adminName">
                    </div>
                    <div class="mb-3">
                        <label for="adminEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="adminEmail">
                    </div>
                    <div class="mb-3">
                        <label for="adminRole" class="form-label">Rôle</label>
                        <select class="form-control" id="adminRole">
                            <option>Sélectionner un rôle</option>
                            <option>Super Admin</option>
                            <option>Manager</option>
                            <option>Modérateur</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="button" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<?php
include '../include/footer-admin.php';
?>
