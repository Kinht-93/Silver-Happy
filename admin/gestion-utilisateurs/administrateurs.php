<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$administrateurs = [];

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
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'active' => 1
        ];
        
        $response = callAPI('http://localhost:8080/api/users', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Administrateur ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'role' => $_POST['role']
        ];
        
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Administrateur modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Administrateur supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/users', 'GET', null, $token);
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
    } elseif (is_array($response)) {
        $adminRoles = ['admin', 'superadmin', 'manager', 'moderateur', 'administrateur'];
        $administrateurs = array_filter($response, function($user) use ($adminRoles) {
            return isset($user['role']) && in_array(strtolower($user['role']), $adminRoles);
        });
        $administrateurs = array_values($administrateurs);
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

<div class="page-title">Gestion des Administrateurs</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-outline-primary">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-primary active">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddAdmin">+ Ajouter un administrateur</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Permissions</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($administrateurs)): ?>
                    <tr><td colspan="7" class="text-center">Aucun administrateur trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($administrateurs as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td>
                                <?php if (in_array(strtolower($admin['role']), ['superadmin', 'admin', 'administrateur'])): ?>
                                    <span class="badge bg-danger">Super Admin</span>
                                <?php elseif (in_array(strtolower($admin['role']), ['manager'])): ?>
                                    <span class="badge bg-warning">Manager</span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?= htmlspecialchars(ucfirst($admin['role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>Tous les droits</td>
                            <td>
                                <?php if ($admin['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($admin['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($admin)) ?>" onclick="viewAdmin(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($admin)) ?>" onclick="editAdmin(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($admin['id_user']) ?>">
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

<div class="modal fade" id="modalAddAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un administrateur</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddAdmin">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="adminFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="adminFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="adminLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="adminEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminRole" class="form-label">Rôle *</label>
                        <select class="form-control" id="adminRole" name="role" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="superadmin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="moderateur">Modérateur</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddAdmin" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier administrateur</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditAdmin">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editAdminId" name="id">
                    <div class="mb-3">
                        <label for="editAdminFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editAdminFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAdminLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editAdminLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAdminEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editAdminEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAdminRole" class="form-label">Rôle *</label>
                        <select class="form-control" id="editAdminRole" name="role" required>
                            <option value="superadmin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="moderateur">Modérateur</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditAdmin" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewAdmin(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    alert('Administrateur: ' + user.first_name + ' ' + user.last_name + '\nRôle: ' + user.role + '\nEmail: ' + user.email);
}

function editAdmin(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editAdminId').value = user.id_user;
    document.getElementById('editAdminFirstName').value = user.first_name;
    document.getElementById('editAdminLastName').value = user.last_name;
    document.getElementById('editAdminEmail').value = user.email;
    document.getElementById('editAdminRole').value = user.role || '';
    openModal('modalEditAdmin');
}
</script>

<?php
include '../include/footer-admin.php';
?>
