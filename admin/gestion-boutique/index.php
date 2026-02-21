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
        <button class="btn btn-sm btn-success">+ Ajouter un article</button>
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

<?php
include '../include/footer-admin.php';
?>
