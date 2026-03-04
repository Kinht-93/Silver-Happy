<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO service_types (id_service_type, name, description, hourly_rate, certification_required, id_service_category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                uniqid('styp_'),
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['hourly_rate'] ?? 0,
                $_POST['certification_required'] ?? 0,
                $_POST['id_service_category']
            ]);
            $message = "Type ajouté avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE service_types SET name=?, description=?, id_service_category=? WHERE id_service_type=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['id_service_category'],
                $_POST['id']
            ]);
            $message = "Type modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmtDb = $pdo->prepare("DELETE FROM service_types WHERE id_service_type=?");
            $stmtDb->execute([$_POST['id']]);
            $message = "Type supprimé.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT st.id_service_type, st.name, st.description, sc.name as category_name, st.id_service_category,
           (SELECT COUNT(*) FROM service_requests sr INNER JOIN show_type sht ON sr.id_request = sht.id_request WHERE sht.id_service_type = st.id_service_type) as prestations
    FROM service_types st
    LEFT JOIN service_categories sc ON st.id_service_category = sc.id_service_category
    ORDER BY sc.name, st.name
";
$types = $pdo->query($query)->fetchAll();
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

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
        <button class="btn btn-sm btn-success" data-modal="modalAddType">+ Ajouter un type</button>
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
                        <?php if (empty($types)): ?>
                            <tr><td colspan="6" class="text-center">Aucun type trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><?= htmlspecialchars($type['name']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($type['category_name']) ?></span></td>
                                    <td><?= (int)$type['prestations'] ?></td>
                                    <td><?= htmlspecialchars($type['description'] ?: 'Aucune description') ?></td>
                                    <td><span class="badge bg-success">Actif</span></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-type="<?= htmlspecialchars(json_encode($type)) ?>" onclick="editType(this)"><i class="bi bi-pencil"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($type['id_service_type']) ?>">
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

<div class="modal fade" id="modalAddType" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un type</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddType">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="typeName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="typeName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="typeCategory" class="form-label">Catégorie parent *</label>
                        <select class="form-control" id="typeCategory" name="id_service_category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php
                            $categories = $pdo->query("SELECT id_service_category, name FROM service_categories ORDER BY name")->fetchAll();
                            foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id_service_category']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="typeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="typeDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddType" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditType" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le type</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditType">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editTypeId" name="id">
                    <div class="mb-3">
                        <label for="editTypeName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editTypeName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTypeCategory" class="form-label">Catégorie parent *</label>
                        <select class="form-control" id="editTypeCategory" name="id_service_category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php
                            $categories = $pdo->query("SELECT id_service_category, name FROM service_categories ORDER BY name")->fetchAll();
                            foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id_service_category']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editTypeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editTypeDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditType" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function editType(btn) {
    const type = JSON.parse(btn.getAttribute('data-type'));
    document.getElementById('editTypeId').value = type.id_service_type;
    document.getElementById('editTypeName').value = type.name || '';
    document.getElementById('editTypeCategory').value = type.id_service_category || '';
    document.getElementById('editTypeDescription').value = type.description || '';
    openModal('modalEditType');
}
</script>

<?php
include '../include/footer-admin.php';
?>
