<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO service_categories (id_service_category, name, description) VALUES (?, ?, ?)");
            $stmt->execute([
                uniqid('cat_'),
                $_POST['name'],
                $_POST['icon'] ?? null
            ]);
            $message = "Catégorie ajoutée avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE service_categories SET name=?, description=? WHERE id_service_category=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['icon'] ?? null,
                $_POST['id']
            ]);
            $message = "Catégorie modifiée avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmtDb = $pdo->prepare("DELETE FROM service_categories WHERE id_service_category=?");
            $stmtDb->execute([$_POST['id']]);
            $message = "Catégorie supprimée.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT c.id_service_category, c.name, c.description as icon,
           (SELECT COUNT(*) FROM service_types st WHERE st.id_service_category = c.id_service_category) as prestations
    FROM service_categories c
    ORDER BY c.name
";
$categories = $pdo->query($query)->fetchAll();

$total_categories = count($categories);
$total_prestations = 0;
foreach ($categories as $cat) {
    $total_prestations += $cat['prestations'];
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des catégories de prestations</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-primary active">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-outline-primary">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-outline-primary">Réalisées</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddCategory">+ Ajouter une catégorie</button>
    </div>

</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Icône</th>
                            <th>Prestations</th>
                            <th>Visible</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" class="text-center">Aucune catégorie trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $categorie): ?>
                                <tr>
                                    <td><?= htmlspecialchars($categorie['name']) ?></td>
                                    <td><i class="<?= htmlspecialchars($categorie['icon'] ?: 'bi bi-folder') ?>"></i></td>
                                    <td><?= (int)$categorie['prestations'] ?></td>
                                    <td><span class="badge bg-success">Oui</span></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cat="<?= htmlspecialchars(json_encode($categorie)) ?>" onclick="editCategory(this)"><i class="bi bi-pencil"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($categorie['id_service_category']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h5 class="mb-3">Statistiques</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Total catégories</span>
                    <strong><?= (int)$total_categories ?></strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Catégories visibles</span>
                    <strong><?= (int)$total_categories ?></strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Prestations liées</span>
                    <strong><?= (int)$total_prestations ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddCategory">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryIcon" class="form-label">Icône</label>
                        <input type="text" class="form-control" id="categoryIcon" name="icon" placeholder="ex: bi-briefcase">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddCategory" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditCategory">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editCategoryId" name="id">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryIcon" class="form-label">Icône</label>
                        <input type="text" class="form-control" id="editCategoryIcon" name="icon" placeholder="ex: bi-briefcase">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditCategory" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function editCategory(btn) {
    const category = JSON.parse(btn.getAttribute('data-cat'));
    document.getElementById('editCategoryId').value = category.id_service_category;
    document.getElementById('editCategoryName').value = category.name || '';
    document.getElementById('editCategoryIcon').value = category.icon || '';
    openModal('modalEditCategory');
}
</script>

<?php
include '../include/footer-admin.php';
?>
