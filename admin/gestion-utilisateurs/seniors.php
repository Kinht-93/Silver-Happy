<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $id = uniqid('usr_');
            $password = password_hash('default123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (id_user, first_name, last_name, email, role, birth_date, active, created_at, password) VALUES (?, ?, ?, ?, 'senior', ?, 1, NOW(), ?)");
            $stmt->execute([
                $id,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['birth_date'] ?: null,
                $password
            ]);
            $message = "Senior ajouté avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, birth_date=? WHERE id_user=?");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['birth_date'] ?: null,
                $_POST['id']
            ]);
            $message = "Senior modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id_user=?");
            $stmt->execute([$_POST['id']]);
            $message = "Senior supprimé.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT u.id_user, u.last_name, u.first_name, u.email, u.birth_date, u.active, u.created_at, 
           st.name as subscription_name
    FROM users u
    LEFT JOIN subscribed sub ON u.id_user = sub.id_user
    LEFT JOIN subscription_types st ON sub.id_subscription_type = st.id_subscription_type
    WHERE u.role = 'senior'
    ORDER BY u.created_at DESC
";
$seniors = $pdo->query($query)->fetchAll();

function getAge($birth_date) {
    if (!$birth_date) return 'N/A';
    $from = new DateTime($birth_date);
    $to = new DateTime('today');
    return $from->diff($to)->y . ' ans';
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des Seniors</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-primary active">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddSenior">+ Ajouter un senior</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Âge</th>
                    <th>Statut abonnement</th>
                    <th>Statut</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($seniors)): ?>
                    <tr><td colspan="7" class="text-center">Aucun senior trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($seniors as $senior): ?>
                        <tr>
                            <td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
                            <td><?= htmlspecialchars($senior['email']) ?></td>
                            <td><?= getAge($senior['birth_date']) ?></td>
                            <td>
                                <?php if ($senior['subscription_name']): ?>
                                    <span class="badge bg-success"><?= htmlspecialchars($senior['subscription_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($senior['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($senior['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($senior)) ?>" onclick="viewSenior(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($senior)) ?>" onclick="loadSeniorData(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce senior ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($senior['id_user']) ?>">
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

<div class="modal fade" id="modalAddSenior" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un senior</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddSenior">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="seniorFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="seniorFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="seniorLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="seniorLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="seniorEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="seniorEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="seniorBirthDate" class="form-label">Date de naissance</label>
                        <input type="date" class="form-control" id="seniorBirthDate" name="birth_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddSenior" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditSenior" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier un senior</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditSenior">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editSeniorId" name="id">
                    <div class="mb-3">
                        <label for="editSeniorFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editSeniorFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSeniorLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editSeniorLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSeniorEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editSeniorEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSeniorBirthDate" class="form-label">Date de naissance</label>
                        <input type="date" class="form-control" id="editSeniorBirthDate" name="birth_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditSenior" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalViewSenior" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du senior</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body" id="seniorDetailContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function loadSeniorData(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editSeniorId').value = user.id_user;
    document.getElementById('editSeniorFirstName').value = user.first_name;
    document.getElementById('editSeniorLastName').value = user.last_name;
    document.getElementById('editSeniorEmail').value = user.email;
    document.getElementById('editSeniorBirthDate').value = user.birth_date || '';
    openModal('modalEditSenior');
}

function viewSenior(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    const age = calculateAge(user.birth_date);
    let html = `
        <p><strong>Nom:</strong> ${user.first_name} ${user.last_name}</p>
        <p><strong>Email:</strong> ${user.email}</p>
        <p><strong>Âge:</strong> ${age || 'Non renseigné'}</p>
        <p><strong>Statut:</strong> ${user.active ? 'Actif' : 'Inactif'}</p>
        <p><strong>Date d'inscription:</strong> ${new Date(user.created_at).toLocaleDateString('fr-FR')}</p>
    `;
    document.getElementById('seniorDetailContent').innerHTML = html;
    openModal('modalViewSenior');
}

function calculateAge(birthDate) {
    if (!birthDate) return null;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age + ' ans';
}
</script>
