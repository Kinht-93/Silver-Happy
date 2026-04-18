<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$seniors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'role' => 'senior',
            'active' => 1
        ];
        
        $response = callAPI('http://localhost:8080/api/users', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Senior ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email']
        ];
        
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Senior modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Senior supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/users', 'GET', null, $token);
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
    } elseif (is_array($response)) {
        $seniors = array_filter($response, function($user) {
            return isset($user['role']) && $user['role'] === 'senior';
        });
        $seniors = array_values($seniors);
    } else {
        $message = "Format de réponse invalide de l'API.";
        $messageType = "warning";
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
                    <th>Statut</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($seniors)): ?>
                    <tr><td colspan="5" class="text-center">Aucun senior trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($seniors as $senior): ?>
                        <tr>
                            <td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
                            <td><?= htmlspecialchars($senior['email']) ?></td>
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
    let html = `
        <p><strong>Nom:</strong> ${user.first_name} ${user.last_name}</p>
        <p><strong>Email:</strong> ${user.email}</p>
        <p><strong>Statut:</strong> ${user.active ? 'Actif' : 'Inactif'}</p>
        <p><strong>Date d'inscription:</strong> ${new Date(user.created_at).toLocaleDateString('fr-FR')}</p>
    `;
    document.getElementById('seniorDetailContent').innerHTML = html;
    openModal('modalViewSenior');
}

function loadSeniorData(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editSeniorId').value = user.id_user;
    document.getElementById('editSeniorFirstName').value = user.first_name;
    document.getElementById('editSeniorLastName').value = user.last_name;
    document.getElementById('editSeniorEmail').value = user.email;
    openModal('modalEditSenior');
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
