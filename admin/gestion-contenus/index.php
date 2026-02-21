<?php
include '../include/header-admin.php';
?>

<div class="page-title">Gestion des contenus</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-primary active">Conseils</a>
            <a href="./langues.php" class="btn btn-sm btn-outline-primary">Langues & traductions</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success">+ Ajouter un conseil</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Auteur</th>
                    <th>Date création</th>
                    <th>Vues</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>10 conseils pour une retraite active</strong></td>
                    <td>Santé</td>
                    <td>Sylvie Moreau</td>
                    <td>12/02/2026</td>
                    <td>245</td>
                    <td><span class="badge bg-success">Publié</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Nutrition équilibrée après 60 ans</strong></td>
                    <td>Nutrition</td>
                    <td>Marc Lemoine</td>
                    <td>08/02/2026</td>
                    <td>189</td>
                    <td><span class="badge bg-success">Publié</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Exercices simples pour améliorer la mobilité</strong></td>
                    <td>Exercice</td>
                    <td>Carole Vincent</td>
                    <td>05/02/2026</td>
                    <td>156</td>
                    <td><span class="badge bg-success">Publié</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Bien dormir : conseils pratiques</strong></td>
                    <td>Santé</td>
                    <td>Sylvie Moreau</td>
                    <td>02/02/2026</td>
                    <td>312</td>
                    <td><span class="badge bg-success">Publié</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Les bienfaits des loisirs créatifs</strong></td>
                    <td>Bien-être</td>
                    <td>Marc Lemoine</td>
                    <td>30/01/2026</td>
                    <td>0</td>
                    <td><span class="badge bg-warning">Brouillon</span></td>
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
