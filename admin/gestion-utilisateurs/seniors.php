<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des Seniors</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-primary active">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success">+ Ajouter un senior</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Âge</th>
                    <th>Statut abonnement</th>
                    <th>Statut</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Marie Dupont</td>
                    <td>marie.dupont@email.com</td>
                    <td>72 ans</td>
                    <td><span class="badge bg-success">Abonné</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>15/01/2026</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Jean Martin</td>
                    <td>jean.martin@email.com</td>
                    <td>68 ans</td>
                    <td><span class="badge bg-warning">Essai</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>20/01/2026</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Fatima Ahmed</td>
                    <td>fatima.ahmed@email.com</td>
                    <td>75 ans</td>
                    <td><span class="badge bg-success">Abonné</span></td>
                    <td><span class="badge bg-secondary">Inactif</span></td>
                    <td>05/01/2026</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Michèle Bernard</td>
                    <td>michele.bernard@email.com</td>
                    <td>69 ans</td>
                    <td><span class="badge bg-success">Abonné</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>12/01/2026</td>
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
