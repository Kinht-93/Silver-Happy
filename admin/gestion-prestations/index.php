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
            $message = "Prestation ajoutée avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE service_types SET name=?, description=?, hourly_rate=? WHERE id_service_type=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['hourly_rate'] ?? 0,
                $_POST['id']
            ]);
            $message = "Prestation modifiée avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmtDb = $pdo->prepare("DELETE FROM service_types WHERE id_service_type=?");
            $stmtDb->execute([$_POST['id']]);
            $message = "Prestation supprimée.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT st.id_service_type, st.name as titre, st.hourly_rate as prix, st.description, st.id_service_category,
           sc.name as categorie,
           (SELECT COUNT(*) FROM show_type s WHERE s.id_service_type = st.id_service_type) as reservations
    FROM service_types st
    LEFT JOIN service_categories sc ON st.id_service_category = sc.id_service_category
    ORDER BY sc.name, st.name
";
$prestations = $pdo->query($query)->fetchAll();
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
                            <td><?= htmlspecialchars($prestation['titre']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($prestation['categorie']) ?></span></td>
                            <td><?= htmlspecialchars($prestation['titre']) ?></td>
                            <td>Générique</td>
                            <td><?= number_format($prestation['prix'], 2) ?>€</td>
                            <td><?= (int)$prestation['reservations'] ?></td>
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
                            $categories = $pdo->query("SELECT id_service_category, name FROM service_categories")->fetchAll();
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
    document.getElementById('editServiceTitle').value = type.titre || '';
    document.getElementById('editServiceDescription').value = type.description || '';
    document.getElementById('editServicePrice').value = parseFloat(type.prix) || 0;
    openModal('modalEditService');
}

function viewServiceDetails(btn) {
    const type = JSON.parse(btn.getAttribute('data-type'));
    alert('Détails de la prestation: ' + type.titre + '\nCatégorie: ' + type.categorie + '\nPrix horaire: ' + type.prix + ' €\nDescription: ' + (type.description || 'Aucune description'));
}
</script>

<?php
include '../include/footer-admin.php';
?>