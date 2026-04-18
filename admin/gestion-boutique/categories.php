<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$categories = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $message = "Les catégories sont créées dynamiquement lors de l'ajout d'un produit.";
        $messageType = "info";
    } elseif ($action === 'update') {
        $message = "Les catégories ne peuvent pas être modifiées directement. Mettez à jour les produits individuellement.";
        $messageType = "info";
    } elseif ($action === 'delete') {
        $message = "Les catégories ne peuvent pas être supprimées. Mettez à jour les produits individuellement.";
        $messageType = "info";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/product-categories', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $categories = [];
    } elseif (is_array($response)) {
        $categories = $response;
    }
} else {
    $message = "Token d'authentification manquant.";
    $messageType = "danger";
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

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
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" class="text-center">Aucune catégorie trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $categorie): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($categorie['name'] ?? 'Sans catégorie') ?></strong></td>
                            <td>Catégorie de produits</td>
                            <td><?= (int)($categorie['articles'] ?? 0) ?></td>
                            <td>Affichée</td>
                            <td><span class="badge bg-success">Actif</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-cat="<?= htmlspecialchars($categorie['name']) ?>" onclick="editShopCategory(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($categorie['name']) ?>">
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

<div class="modal fade" id="modalAddShopCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddShopCategory">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="shopCategoryName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="shopCategoryName" name="category" required>
                    </div>
                    <div class="mb-3">
                        <label for="shopCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="shopCategoryDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddShopCategory" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditShopCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditShopCategory">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editShopCategoryId" name="old_category">
                    <div class="mb-3">
                        <label for="editShopCategoryName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editShopCategoryName" name="category" required>
                    </div>
                    <div class="mb-3">
                        <label for="editShopCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editShopCategoryDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditShopCategory" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function editShopCategory(btn) {
    const categoryName = btn.getAttribute('data-cat');
    document.getElementById('editShopCategoryId').value = categoryName;
    document.getElementById('editShopCategoryName').value = categoryName;
    document.getElementById('editShopCategoryDescription').value = 'Catégorie de produits';
    openModal('modalEditShopCategory');
}
</script>

<?php
include '../include/footer-admin.php';
?>
