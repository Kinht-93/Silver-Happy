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
        <button class="btn btn-sm btn-success">+ Ajouter un type</button>
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

<?php
include '../include/footer-admin.php';
?>
