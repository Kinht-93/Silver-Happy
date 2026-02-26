<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des Employés</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-primary active">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddEmployee">+ Ajouter un employé</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Poste</th>
                    <th>Département</th>
                    <th>Statut contrat</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sylvie Moreau</td>
                    <td>sylvie.moreau@silverhappy.fr</td>
                    <td>Responsable RH</td>
                    <td>Ressources Humaines</td>
                    <td><span class="badge bg-success">CDI</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Thomas Blanc</td>
                    <td>thomas.blanc@silverhappy.fr</td>
                    <td>Développeur</td>
                    <td>IT</td>
                    <td><span class="badge bg-success">CDI</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Carole Vincent</td>
                    <td>carole.vincent@silverhappy.fr</td>
                    <td>Gestionnaire prestations</td>
                    <td>Opérations</td>
                    <td><span class="badge bg-warning">CDD</span></td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Marc Lemoine</td>
                    <td>marc.lemoine@silverhappy.fr</td>
                    <td>Responsable Client</td>
                    <td>Support</td>
                    <td><span class="badge bg-success">CDI</span></td>
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

<div class="modal fade" id="modalAddEmployee" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un employé</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="employeeName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="employeeName">
                    </div>
                    <div class="mb-3">
                        <label for="employeeEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="employeeEmail">
                    </div>
                    <div class="mb-3">
                        <label for="employeePosition" class="form-label">Poste</label>
                        <input type="text" class="form-control" id="employeePosition">
                    </div>
                    <div class="mb-3">
                        <label for="employeeDepartment" class="form-label">Département</label>
                        <select class="form-control" id="employeeDepartment">
                            <option>Sélectionner un département</option>
                            <option>Ressources Humaines</option>
                            <option>IT</option>
                            <option>Opérations</option>
                            <option>Support</option>
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
