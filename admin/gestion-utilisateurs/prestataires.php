<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des Prestataires</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-primary active">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddProvider">+ Ajouter un prestataire</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Spécialité</th>
                    <th>Prestations</th>
                    <th>Validation</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sophie Dubois</td>
                    <td>sophie.dubois@email.com</td>
                    <td>Aide à domicile</td>
                    <td>15</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Pierre Lefeuvre</td>
                    <td>pierre.lefeuvre@email.com</td>
                    <td>Coaching bien-être</td>
                    <td>8</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Laurent Gauthier</td>
                    <td>laurent.gauthier@email.com</td>
                    <td>Cours informatique</td>
                    <td>12</td>
                    <td><span class="badge bg-warning">En attente</span></td>
                    <td><span class="badge bg-info">Nouveau</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Isabelle Rousseau</td>
                    <td>isabelle.rousseau@email.com</td>
                    <td>Loisirs culturels</td>
                    <td>7</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
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

<div class="modal fade" id="modalAddProvider" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="providerName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="providerName">
                    </div>
                    <div class="mb-3">
                        <label for="providerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="providerEmail">
                    </div>
                    <div class="mb-3">
                        <label for="providerSpecialty" class="form-label">Spécialité</label>
                        <input type="text" class="form-control" id="providerSpecialty">
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
