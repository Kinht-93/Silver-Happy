<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion de la boutique</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-primary active">Articles</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./commandes.php" class="btn btn-sm btn-outline-primary">Commandes</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddArticle">+ Ajouter un article</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Ventes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Coussin ergonomique</strong></td>
                    <td>Confort</td>
                    <td>45,99€</td>
                    <td><span class="badge bg-success">15</span></td>
                    <td>45</td>
                    <td><span class="badge bg-success">En stock</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Lampe de lecture LED</strong></td>
                    <td>Éclairage</td>
                    <td>32,50€</td>
                    <td><span class="badge bg-success">8</span></td>
                    <td>32</td>
                    <td><span class="badge bg-success">En stock</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Baume articulaires naturel</strong></td>
                    <td>Bien-être</td>
                    <td>24,99€</td>
                    <td><span class="badge bg-warning">3</span></td>
                    <td>78</td>
                    <td><span class="badge bg-warning">Stock faible</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Bracelet d'alerte médicale</strong></td>
                    <td>Sécurité</td>
                    <td>89,99€</td>
                    <td><span class="badge bg-danger">0</span></td>
                    <td>12</td>
                    <td><span class="badge bg-danger">Rupture stock</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Téléphone senior simplifié</strong></td>
                    <td>Électronique</td>
                    <td>129,00€</td>
                    <td><span class="badge bg-success">6</span></td>
                    <td>18</td>
                    <td><span class="badge bg-success">En stock</span></td>
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

<div class="modal fade" id="modalAddArticle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un article</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="articleName" class="form-label">Nom du produit</label>
                        <input type="text" class="form-control" id="articleName">
                    </div>
                    <div class="mb-3">
                        <label for="articleCategory" class="form-label">Catégorie</label>
                        <select class="form-control" id="articleCategory">
                            <option>Sélectionner une catégorie</option>
                            <option>Confort</option>
                            <option>Bien-être</option>
                            <option>Électronique</option>
                            <option>Sécurité</option>
                            <option>Éclairage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="articlePrice" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="articlePrice" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="articleStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="articleStock">
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
