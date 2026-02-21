<?php
include './include/header-admin.php';
?>

<div class="page-title">Gestion des événements</div>

<div class="row mb-4">
    <div class="col">
        <input type="text" class="form-control" style="max-width: 250px;" placeholder="Rechercher un événement...">
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success">+ Créer un événement</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Localisation</th>
                    <th>Organisateur</th>
                    <th>Participants</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Journée portes ouvertes</strong></td>
                    <td>25/03/2026</td>
                    <td><i class="bi bi-geo-alt"></i> Paris</td>
                    <td>Sylvie Moreau</td>
                    <td>45</td>
                    <td><span class="badge bg-warning">Planification</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Conférence bien vieillir</strong></td>
                    <td>10/04/2026</td>
                    <td><i class="bi bi-geo-alt"></i> Lyon</td>
                    <td>Marc Lemoine</td>
                    <td>32</td>
                    <td><span class="badge bg-warning">Planification</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Sortie au musée</strong></td>
                    <td>04/05/2026</td>
                    <td><i class="bi bi-geo-alt"></i> Bordeaux</td>
                    <td>Carole Vincent</td>
                    <td>28</td>
                    <td><span class="badge bg-info">Confirmé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Atelier cuisine santé</strong></td>
                    <td>12/03/2026</td>
                    <td><i class="bi bi-geo-alt"></i> Paris</td>
                    <td>Isabelle Rousseau</td>
                    <td>20</td>
                    <td><span class="badge bg-success">Complété</span></td>
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
include './include/footer-admin.php';
?>
