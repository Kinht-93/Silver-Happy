<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if (!isset($pdo)) {
    die('Erreur: Connexion à la base de données non disponible');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $pdo->beginTransaction();
            $id_user = uniqid('usr_');
            $password = password_hash('default123', PASSWORD_DEFAULT);
            $siret = substr(md5(uniqid()), 0, 14);
            
            $stmt = $pdo->prepare("INSERT INTO users (id_user, first_name, last_name, email, role, phone, active, created_at, password, siret_number, company_name, validation_status) VALUES (?, ?, ?, ?, 'prestataire', ?, 1, NOW(), ?, ?, ?, 'En attente')");
            $companyName = !empty($_POST['company_name']) ? $_POST['company_name'] : 'Entreprise ' . substr($id_user, 4, 6);
            $stmt->execute([$id_user, $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'] ?: null, $password, $siret, $companyName]);
            
            $pdo->commit();
            $message = "Prestataire ajouté avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, validation_status=? WHERE id_user=?");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'] ?: null,
                $_POST['validation_status'] ?? 'En attente',
                $_POST['id']
            ]);
            $message = "Prestataire modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id_user=?")->execute([$_POST['id']]);
            $message = "Prestataire supprimé.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

try {
    $query = "
        SELECT u.id_user, u.last_name, u.first_name, u.email, u.phone, u.active, 
               u.company_name, u.siret_number, u.validation_status,
               (SELECT COUNT(*) FROM contracts c WHERE c.id_user = u.id_user) as prestations_count
        FROM users u
        WHERE u.role = 'prestataire'
        ORDER BY u.created_at DESC
    ";
    $prestataires = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $message = "Erreur de requête: " . $e->getMessage();
    $messageType = "danger";
    $prestataires = [];
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des Prestataires</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-primary active">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddProvider">+ Ajouter un prestataire</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Spécialité</th>
                    <th>Prestations</th>
                    <th>Validation</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestataires)): ?>
                    <tr><td colspan="7" class="text-center">Aucun prestataire trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($prestataires as $prestataire): ?>
                        <tr>
                            <td><?= htmlspecialchars($prestataire['first_name'] . ' ' . $prestataire['last_name']) ?></td>
                            <td><?= htmlspecialchars($prestataire['email']) ?></td>
                            <td><?= htmlspecialchars($prestataire['company_name']) ?></td>
                            <td><?= (int)$prestataire['prestations_count'] ?></td>
                            <td>
                                <?php if ($prestataire['validation_status'] == 'Validé'): ?>
                                    <span class="badge bg-success">Validé</span>
                                <?php elseif ($prestataire['validation_status'] == 'En attente'): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($prestataire['validation_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prestataire['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($prestataire)) ?>" onclick="viewProvider(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($prestataire)) ?>" onclick="editProvider(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce prestataire ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($prestataire['id_user']) ?>">
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

<div class="modal fade" id="modalAddProvider" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddProvider">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="providerFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="providerFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="providerLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="providerEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerSpecialty" class="form-label">Spécialité/Entreprise</label>
                        <input type="text" class="form-control" id="providerSpecialty" name="company_name">
                    </div>
                    <div class="mb-3">
                        <label for="providerPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="providerPhone" name="phone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddProvider" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditProvider" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditProvider">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editProviderId" name="id">
                    <div class="mb-3">
                        <label for="editProviderFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editProviderFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editProviderLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editProviderEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="editProviderPhone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="editProviderCompany" class="form-label">Spécialité/Entreprise</label>
                        <input type="text" class="form-control" id="editProviderCompany" name="company_name">
                    </div>
                    <div class="mb-3">
                        <label for="editProviderSiret" class="form-label">SIRET</label>
                        <input type="text" class="form-control" id="editProviderSiret" name="siret_number" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderValidation" class="form-label">Statut de validation</label>
                        <select class="form-select" id="editProviderValidation" name="validation_status">
                            <option value="En attente">En attente</option>
                            <option value="Validé">Validé</option>
                            <option value="Rejeté">Rejeté</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditProvider" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewProvider(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    alert('Prestataire: ' + user.first_name + ' ' + user.last_name + '\nSpécialité: ' + (user.company_name || 'N/A') + '\nEmail: ' + user.email + '\nStatut Validation: ' + user.validation_status);
}

function editProvider(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editProviderId').value = user.id_user;
    document.getElementById('editProviderFirstName').value = user.first_name;
    document.getElementById('editProviderLastName').value = user.last_name;
    document.getElementById('editProviderEmail').value = user.email;
    document.getElementById('editProviderPhone').value = user.phone || '';
    document.getElementById('editProviderCompany').value = user.company_name || '';
    document.getElementById('editProviderSiret').value = user.siret_number || '';
    document.getElementById('editProviderValidation').value = user.validation_status || 'En attente';
    openModal('modalEditProvider');
}
</script>

<?php
include '../include/footer-admin.php';
?>