<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des catégories de prestations</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-primary active">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-outline-primary">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-outline-primary">Réalisées</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddCategory">+ Ajouter une catégorie</button>
    </div>

</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Icône</th>
                            <th>Prestations</th>
                            <th>Visible</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Service</td>
                            <td><i class="bi bi-briefcase"></i></td>
                            <td>5</td>
                            <td><span class="badge bg-success">Oui</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Loisir</td>
                            <td><i class="bi bi-calendar-event"></i></td>
                            <td>8</td>
                            <td><span class="badge bg-success">Oui</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Conseil</td>
                            <td><i class="bi bi-book"></i></td>
                            <td>3</td>
                            <td><span class="badge bg-success">Oui</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Produit</td>
                            <td><i class="bi bi-shop"></i></td>
                            <td>12</td>
                            <td><span class="badge bg-success">Oui</span></td>
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
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h5 class="mb-3">Statistiques</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Total catégories</span>
                    <strong>4</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Catégories visibles</span>
                    <strong>4</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Prestations liées</span>
                    <strong>28</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="categoryName">
                    </div>
                    <div class="mb-3">
                        <label for="categoryIcon" class="form-label">Icône</label>
                        <input type="text" class="form-control" id="categoryIcon" placeholder="ex: bi-briefcase">
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
