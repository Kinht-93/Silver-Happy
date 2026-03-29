<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$produits = [];

function callAPI($url, $method = 'GET', $data = null, $token = '') {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "X-Token: {$token}\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Impossible de se connecter à l\'API'];
    }
    
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'category' => $_POST['category'],
            'price' => (float)($_POST['price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0)
        ];
        
        $response = callAPI('http://localhost:8080/api/products', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Produit ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'name' => $_POST['name'],
            'price' => (float)($_POST['price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0)
        ];
        
        $response = callAPI("http://localhost:8080/api/products/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Produit modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/products/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Produit supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/products', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $produits = [];
    } elseif (is_array($response)) {
        $produits = $response;
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
        <button class="btn btn-sm btn-success" data-modal="modalAddArticle">+ Ajouter un article</button>
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
                <?php if (empty($produits)): ?>
                    <tr><td colspan="7" class="text-center">Aucun produit trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($produit['name']) ?></strong></td>
                            <td><?= htmlspecialchars($produit['category']) ?></td>
                            <td><?= number_format($produit['price'], 2) ?>€</td>
                            <td>
                                <?php if ($produit['stock'] > 10): ?>
                                    <span class="badge bg-success"><?= (int)$produit['stock'] ?></span>
                                <?php elseif ($produit['stock'] > 0): ?>
                                    <span class="badge bg-warning"><?= (int)$produit['stock'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)($produit['sales'] ?? 0) ?></td>
                            <td>
                                <?php if ($produit['stock'] > 10): ?>
                                    <span class="badge bg-success">En stock</span>
                                <?php elseif ($produit['stock'] > 0): ?>
                                    <span class="badge bg-warning">Stock faible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rupture stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-product="<?= htmlspecialchars(json_encode($produit)) ?>" onclick="viewProduct(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-product="<?= htmlspecialchars(json_encode($produit)) ?>" onclick="editProduct(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($produit['id_product']) ?>">
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

<div class="modal fade" id="modalAddArticle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un article</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddProduct">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="articleName" class="form-label">Nom du produit *</label>
                        <input type="text" class="form-control" id="articleName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="articleCategory" class="form-label">Catégorie *</label>
                        <select class="form-control" id="articleCategory" name="category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option value="Confort">Confort</option>
                            <option value="Bien-être">Bien-être</option>
                            <option value="Électronique">Électronique</option>
                            <option value="Sécurité">Sécurité</option>
                            <option value="Éclairage">Éclairage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="articlePrice" class="form-label">Prix (€) *</label>
                        <input type="number" class="form-control" id="articlePrice" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="articleStock" class="form-label">Stock *</label>
                        <input type="number" class="form-control" id="articleStock" name="stock" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddProduct" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditProduct" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier produit</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditProduct">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editProductId" name="id">
                    <div class="mb-3">
                        <label for="editArticleName" class="form-label">Nom du produit *</label>
                        <input type="text" class="form-control" id="editArticleName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editArticlePrice" class="form-label">Prix (€) *</label>
                        <input type="number" class="form-control" id="editArticlePrice" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="editArticleStock" class="form-label">Stock *</label>
                        <input type="number" class="form-control" id="editArticleStock" name="stock" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditProduct" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewProduct(btn) {
    const productData = JSON.parse(btn.getAttribute('data-product'));
    alert('Produit: ' + productData.name + '\nCatégorie: ' + productData.category + '\nPrix: ' + productData.price + ' €\nStock: ' + productData.stock);
}

function editProduct(btn) {
    const productData = JSON.parse(btn.getAttribute('data-product'));
    document.getElementById('editProductId').value = productData.id_product || '';
    document.getElementById('editArticleName').value = productData.name || '';
    document.getElementById('editArticlePrice').value = parseFloat(productData.price) || 0;
    document.getElementById('editArticleStock').value = parseInt(productData.stock) || 0;
    openModal('modalEditProduct');
}
</script>

<?php
include '../include/footer-admin.php';
?>
