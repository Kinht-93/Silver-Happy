<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$prestations = [];
$categories = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? null,
            'hourly_rate' => (float)($_POST['hourly_rate'] ?? 0),
            'certification_required' => isset($_POST['certification_required']) ? true : false,
            'id_service_category' => $_POST['id_service_category']
        ];
        
        $response = callAPI('http://localhost:8080/api/service-types', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Prestation ajoutée avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? null,
            'hourly_rate' => (float)($_POST['hourly_rate'] ?? 0),
            'id_service_category' => $_POST['id_service_category'] ?? ''
        ];
        
        $response = callAPI("http://localhost:8080/api/service-types/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Prestation modifiée avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/service-types/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Prestation supprimée.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/service-types-admin', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $prestations = [];
    } elseif (is_array($response)) {
        $prestations = $response;
    }
    
    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories-admin', 'GET', null, $token);
    if (is_array($categoriesResponse) && !isset($categoriesResponse['error'])) {
        $categories = $categoriesResponse;
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
        <button class="btn btn-sm btn-success" data-modal="modalAddService">+ Ajouter une prestation</button>
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
                <?php if (empty($prestations)): ?>
                    <tr><td colspan="8" class="text-center">Aucune prestation trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($prestations as $prestation): ?>
                        <tr>
                            <td><?= htmlspecialchars($prestation['name']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($prestation['category_name'] ?? 'N/A') ?></span></td>
                            <td><?= htmlspecialchars($prestation['name']) ?></td>
                            <td>Générique</td>
                            <td><?= number_format($prestation['hourly_rate'] ?? 0, 2) ?>€</td>
                            <td><?= (int)($prestation['prestations_count'] ?? 0) ?></td>
                            <td><span class="badge bg-success">Validé</span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-type="<?= htmlspecialchars(json_encode($prestation)) ?>" onclick="viewServiceDetails(this)"><i class="bi bi-eye"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-type="<?= htmlspecialchars(json_encode($prestation)) ?>" onclick="editService(this)"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette prestation ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($prestation['id_service_type']) ?>">
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

<div class="modal fade" id="modalAddService" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une prestation</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddService">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="serviceTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="serviceTitle" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="serviceDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="serviceDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="serviceCategory" class="form-label">Catégorie *</label>
                        <select class="form-control" id="serviceCategory" name="id_service_category" required>
                            <option value="">Sélectionner une catégorie</option>
<?php
                            foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id_service_category']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="servicePrice" class="form-label">Prix horaire (€) *</label>
                        <input type="number" class="form-control" id="servicePrice" name="hourly_rate" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="serviceCertification" class="form-label">Certification requise</label>
                        <input type="checkbox" class="form-check-input" id="serviceCertification" name="certification_required" value="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddService" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditService" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier prestation</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditService">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editServiceId" name="id">
                    <div class="mb-3">
                        <label for="editServiceTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="editServiceTitle" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editServiceDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editServiceDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editServiceCategory" class="form-label">Catégorie *</label>
                        <select class="form-control" id="editServiceCategory" name="id_service_category" required>
                            <option value="">Sélectionner une catégorie</option>
<?php
                            foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id_service_category']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editServicePrice" class="form-label">Prix horaire (€) *</label>
                        <input type="number" class="form-control" id="editServicePrice" name="hourly_rate" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditService" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function editService(btn) {
    const type = JSON.parse(btn.getAttribute('data-type'));
    document.getElementById('editServiceId').value = type.id_service_type;
    document.getElementById('editServiceTitle').value = type.name || '';
    document.getElementById('editServiceDescription').value = type.description || '';
    document.getElementById('editServiceCategory').value = type.id_service_category || '';
    document.getElementById('editServicePrice').value = parseFloat(type.hourly_rate) || 0;
    openModal('modalEditService');
}

function viewServiceDetails(btn) {
    const type = JSON.parse(btn.getAttribute('data-type'));
    alert('Détails de la prestation: ' + type.name + '\nCatégorie: ' + (type.category_name || 'N/A') + '\nPrix horaire: ' + type.hourly_rate + ' €\nDescription: ' + (type.description || 'Aucune description'));
}
</script>

<?php
include '../include/footer-admin.php';
?>