<?php
include '../include/header-admin.php';
?>

<div class="page-title">Catégories d'articles</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Articles</a>
            <a href="./categories.php" class="btn btn-sm btn-primary active">Catégories</a>
            <a href="./commandes.php" class="btn btn-sm btn-outline-primary">Commandes</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddShopCategory">+ Ajouter une catégorie</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Articles</th>
                    <th>Stratégie d'affichage</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Confort</strong></td>
                    <td>Produits pour améliorer le confort à domicile</td>
                    <td>12</td>
                    <td>Affichée</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Bien-être</strong></td>
                    <td>Crèmes, baumes et produits de soin naturels</td>
                    <td>8</td>
                    <td>Affichée</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Électronique</strong></td>
                    <td>Téléphones, montres et appareils adaptés aux seniors</td>
                    <td>5</td>
                    <td>Affichée</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Sécurité</strong></td>
                    <td>Bracelets d'alerte et systèmes de sécurité</td>
                    <td>3</td>
                    <td>Affichée</td>
                    <td><span class="badge bg-success">Actif</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Éclairage</strong></td>
                    <td>Lampes et systèmes d'éclairage adaptés</td>
                    <td>6</td>
                    <td>Cachée</td>
                    <td><span class="badge bg-secondary">Inactif</span></td>
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

<div class="modal fade" id="modalAddShopCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="shopCategoryName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="shopCategoryName">
                    </div>
                    <div class="mb-3">
                        <label for="shopCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="shopCategoryDescription" rows="3"></textarea>
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
