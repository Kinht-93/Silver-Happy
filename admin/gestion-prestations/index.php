<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des prestations</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-primary active">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-outline-primary">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-outline-primary">Réalisées</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddService">+ Ajouter une prestation</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Prestataire</th>
                    <th>Prix</th>
                    <th>Réservations</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Aide à domicile</td>
                    <td><span class="badge bg-light text-dark">Service</span></td>
                    <td>Ménage</td>
                    <td>Sophie Dubois</td>
                    <td>29,90€</td>
                    <td>12</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Atelier mémoire</td>
                    <td><span class="badge bg-light text-dark">Loisir</span></td>
                    <td>Atelier</td>
                    <td>Pierre Lefeuvre</td>
                    <td>12,00€</td>
                    <td>24</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Coaching bien-être</td>
                    <td><span class="badge bg-light text-dark">Conseil</span></td>
                    <td>Conseil</td>
                    <td>Pierre Lefeuvre</td>
                    <td>39,00€</td>
                    <td>8</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Sortie culturelle</td>
                    <td><span class="badge bg-light text-dark">Loisir</span></td>
                    <td>Sortie</td>
                    <td>Isabelle Rousseau</td>
                    <td>18,50€</td>
                    <td>35</td>
                    <td><span class="badge bg-success">Validé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Cours de numérique</td>
                    <td><span class="badge bg-light text-dark">Service</span></td>
                    <td>Formation</td>
                    <td>Laurent Gauthier</td>
                    <td>15,00€</td>
                    <td>18</td>
                    <td><span class="badge bg-warning">En attente</span></td>
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

<div class="modal fade" id="modalAddService" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une prestation</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="serviceTitle" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="serviceTitle">
                    </div>
                    <div class="mb-3">
                        <label for="serviceCategory" class="form-label">Catégorie</label>
                        <select class="form-control" id="serviceCategory">
                            <option>Sélectionner une catégorie</option>
                            <option>Service</option>
                            <option>Loisir</option>
                            <option>Conseil</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="serviceType" class="form-label">Type</label>
                        <select class="form-control" id="serviceType">
                            <option>Sélectionner un type</option>
                            <option>Ménage</option>
                            <option>Atelier</option>
                            <option>Formation</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="servicePrice" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="servicePrice" step="0.01">
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
