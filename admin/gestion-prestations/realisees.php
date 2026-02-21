<?php
include '../include/header-admin.php';
?>

<div class="page-title">Prestations réalisées</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-outline-primary">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-primary active">Réalisées</a>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Prestation</th>
                    <th>Senior</th>
                    <th>Prestataire</th>
                    <th>Date</th>
                    <th>Durée</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Avis</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Aide à domicile</td>
                    <td>Marie Dupont</td>
                    <td>Sophie Dubois</td>
                    <td>18/02/2026</td>
                    <td>2h30</td>
                    <td>74,75€</td>
                    <td><span class="badge bg-success">Complétée</span></td>
                    <td><i class="bi bi-star-fill" style="color: #f9a826;"></i> 5/5</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Atelier mémoire</td>
                    <td>Jean Martin</td>
                    <td>Pierre Lefeuvre</td>
                    <td>17/02/2026</td>
                    <td>1h30</td>
                    <td>12,00€</td>
                    <td><span class="badge bg-success">Complétée</span></td>
                    <td><i class="bi bi-star-fill" style="color: #f9a826;"></i> 4/5</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Sortie culturelle</td>
                    <td>Fatima Ahmed</td>
                    <td>Isabelle Rousseau</td>
                    <td>16/02/2026</td>
                    <td>4h</td>
                    <td>18,50€</td>
                    <td><span class="badge bg-success">Complétée</span></td>
                    <td><i class="bi bi-star-fill" style="color: #f9a826;"></i> 5/5</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Coaching bien-être</td>
                    <td>Michèle Bernard</td>
                    <td>Pierre Lefeuvre</td>
                    <td>15/02/2026</td>
                    <td>1h</td>
                    <td>39,00€</td>
                    <td><span class="badge bg-warning">En cours</span></td>
                    <td>-</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Cours de numérique</td>
                    <td>Marie Dupont</td>
                    <td>Laurent Gauthier</td>
                    <td>14/02/2026</td>
                    <td>2h</td>
                    <td>15,00€</td>
                    <td><span class="badge bg-success">Complétée</span></td>
                    <td><i class="bi bi-star-fill" style="color: #f9a826;"></i> 5/5</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../include/footer-admin.php';
?>
