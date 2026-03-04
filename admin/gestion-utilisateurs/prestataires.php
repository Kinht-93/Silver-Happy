<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $pdo->beginTransaction();
            $id_user = uniqid('usr_');
            $id_provider = uniqid('prov_');
            $password = password_hash('default123', PASSWORD_DEFAULT);
            $siret = substr(md5(uniqid()), 0, 14);
            
            $stmt = $pdo->prepare("INSERT INTO users (id_user, first_name, last_name, email, role, phone, active, created_at, password) VALUES (?, ?, ?, ?, 'provider', ?, 1, NOW(), ?)");
            $stmt->execute([$id_user, $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'] ?: null, $password]);
            
            $stmt2 = $pdo->prepare("INSERT INTO providers (id_provider, siret_number, company_name, validation_status) VALUES (?, ?, ?, 'En attente')");
            $companyName = !empty($_POST['company_name']) ? $_POST['company_name'] : 'Entreprise ' . substr($id_user, 4, 6);
            $stmt2->execute([$id_provider, $siret, $companyName]);
            
            $stmt3 = $pdo->prepare("INSERT INTO is_provider (id_user, id_provider) VALUES (?, ?)");
            $stmt3->execute([$id_user, $id_provider]);
            
            $pdo->commit();
            $message = "Prestataire ajouté avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id_user=?");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'] ?: null,
                $_POST['id']
            ]);
            $message = "Prestataire modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmtFetch = $pdo->prepare("SELECT id_provider FROM is_provider WHERE id_user=?");
            $stmtFetch->execute([$_POST['id']]);
            $provId = $stmtFetch->fetchColumn();
            
            $pdo->beginTransaction();
            if ($provId) {
                $pdo->prepare("DELETE FROM is_provider WHERE id_user=?")->execute([$_POST['id']]);
                $pdo->prepare("DELETE FROM users WHERE id_user=?")->execute([$_POST['id']]);
                try {
                    $pdo->prepare("DELETE FROM providers WHERE id_provider=?")->execute([$provId]);
                } catch(PDOException $e) {
                }
            } else {
                $pdo->prepare("DELETE FROM users WHERE id_user=?")->execute([$_POST['id']]);
            }
            $pdo->commit();
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

$query = "
    SELECT u.id_user, u.last_name, u.first_name, u.email, u.phone, u.active, 
           p.company_name, p.validation_status,
           (SELECT COUNT(*) FROM contracts c WHERE c.id_provider = p.id_provider) as prestations_count
    FROM users u
    INNER JOIN is_provider iprov ON u.id_user = iprov.id_user
    INNER JOIN providers p ON iprov.id_provider = p.id_provider
    ORDER BY u.created_at DESC
";
$prestataires = $pdo->query($query)->fetchAll();
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
    openModal('modalEditProvider');
}
</script>

<?php
include '../include/footer-admin.php';
?>