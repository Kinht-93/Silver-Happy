<?php
include '../include/header-admin.php';
?>

<div class="page-title">Commandes</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Articles</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./commandes.php" class="btn btn-sm btn-primary active">Commandes</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Commandes totales</div>
            <div class="stat-value">142</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">En attente</div>
            <div class="stat-value">8</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Livrées</div>
            <div class="stat-value">128</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">Retours</div>
            <div class="stat-value">6</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Article(s)</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Livraison</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>#CMD-2026-0089</strong></td>
                    <td>Marie Dupont</td>
                    <td>Coussin ergonomique (x1) + Lampe LED (x1)</td>
                    <td>78,49€</td>
                    <td>19/02/2026</td>
                    <td>Domicile</td>
                    <td><span class="badge bg-success">Livrée</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#CMD-2026-0090</strong></td>
                    <td>Jean Martin</td>
                    <td>Baume articulaire (x2)</td>
                    <td>49,98€</td>
                    <td>18/02/2026</td>
                    <td>Domicile</td>
                    <td><span class="badge bg-success">Livrée</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#CMD-2026-0091</strong></td>
                    <td>Fatima Ahmed</td>
                    <td>Téléphone senior simplifié (x1)</td>
                    <td>129,00€</td>
                    <td>17/02/2026</td>
                    <td>Domicile</td>
                    <td><span class="badge bg-info">En cours de livraison</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#CMD-2026-0092</strong></td>
                    <td>Michèle Bernard</td>
                    <td>Bracelet d'alerte (x1)</td>
                    <td>89,99€</td>
                    <td>16/02/2026</td>
                    <td>Domicile</td>
                    <td><span class="badge bg-warning">Traitée</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#CMD-2026-0093</strong></td>
                    <td>Pierre Dupui</td>
                    <td>Coussin ergonomique (x1) + Lampe LED (x2)</td>
                    <td>110,49€</td>
                    <td>15/02/2026</td>
                    <td>Retrait</td>
                    <td><span class="badge bg-danger">Retour demandé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../include/footer-admin.php';
?>
