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
        <button class="btn btn-sm btn-success">+ Ajouter un administrateur</button>
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

<?php
include '../include/footer-admin.php';
?>
