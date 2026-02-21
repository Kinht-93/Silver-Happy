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
        <button class="btn btn-sm btn-success">+ Ajouter une prestation</button>
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

<?php
include '../include/footer-admin.php';
?>
