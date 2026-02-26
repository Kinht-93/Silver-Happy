<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des types de prestations</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-primary active">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-outline-primary">Réalisées</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddType">+ Ajouter un type</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom type</th>
                    <th>Catégorie parent</th>
                    <th>Prestations</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ménage</td>
                    <td><span class="badge bg-light text-dark">Service</span></td>
                    <td>3</td>
                    <td>Services de nettoyage du domicile</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Atelier</td>
                    <td><span class="badge bg-light text-dark">Loisir</span></td>
                    <td>5</td>
                    <td>Ateliers créatifs et thématiques</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Formation</td>
                    <td><span class="badge bg-light text-dark">Service</span></td>
                    <td>2</td>
                    <td>Cours et formations diverses</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Sortie</td>
                    <td><span class="badge bg-light text-dark">Loisir</span></td>
                    <td>4</td>
                    <td>Sorties et excursions organisées</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="modalAddType" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un type</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="typeName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="typeName">
                    </div>
                    <div class="mb-3">
                        <label for="typeCategory" class="form-label">Catégorie parent</label>
                        <select class="form-control" id="typeCategory">
                            <option>Sélectionner une catégorie</option>
                            <option>Service</option>
                            <option>Loisir</option>
                            <option>Conseil</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="typeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="typeDescription" rows="3"></textarea>
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
