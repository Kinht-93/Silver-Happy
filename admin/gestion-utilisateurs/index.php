<?php

include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$utilisateurs = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'password'   => $_POST['password'],
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'phone' => $_POST['phone'] ?? '',
            'city' => $_POST['city'] ?? '',
            'active' => (bool)(int)$_POST['active'] ?? 1
        ];
        var_dump(json_encode($data));
        
        $response = callAPI('http://localhost:8080/api/users', 'POST', $data, $token);
        var_dump($response);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Utilisateur ajouté";
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
            'role' => $_POST['role'],
            'phone' => $_POST['phone'] ?? null,
            'city' => $_POST['city'] ?? null,
            'active' => $_POST['active'] ?? 1
        ];
        
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Utilisateur modifié.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Utilisateur supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/users', 'GET', null, $token);

    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
    } elseif (is_array($response) && !empty($response)) {
        $utilisateurs = $response;
    } elseif (is_array($response) && empty($response)) {
        $message = "Aucun utilisateur trouvé.";
        $messageType = "info";
    } else {
        $message = "Format de réponse invalide de l'API: " . gettype($response);
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

<div class="page-title">Gestion utilisateurs</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary active" data-filter="all">Tous</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-filter="senior">Seniors</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-filter="prestataire">Prestataires</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-filter="admin">Administrateurs</button>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddUser">+ Ajouter un utilisateur</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr class="no-users"><td colspan="6" class="text-center">Aucun utilisateur trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr data-role="<?= $user['role'] ?>">
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php
                                $role = strtolower($user['role']);
                                if ($role === 'senior') {
                                    echo '<span class="badge bg-info">Senior</span>';
                                } elseif ($role === 'prestataire') {
                                    echo '<span class="badge bg-warning text-dark">Prestataire</span>';
                                } elseif ($role === 'admin') {
                                    echo '<span class="badge bg-danger">Administrateur</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($role)) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($user['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($user)) ?>" onclick="viewUser(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($user)) ?>" onclick="loadUserData(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id_user']) ?>">
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

<div class="modal fade" id="modalAddUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un utilisateur</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddUser">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="userName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="userName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="userLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="userLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="userPassword" class="form-label">Mot de passe (optionnel)</label>
                        <input type="password" class="form-control" id="userPassword" name="password">
                        <small class="text-muted">Laissez vide pour générer un mot de passe par défaut</small>
                    </div>
                    <div class="mb-3">
                        <label for="userType" class="form-label">Type *</label>
                        <select class="form-control" id="userType" name="role" required>
                            <option value="">Sélectionner un type</option>
                            <option value="senior">Senior</option>
                            <option value="prestataire">Prestataire</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="userPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="userPhone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="userCity" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="userCity" name="city">
                    </div>
                    <div class="mb-3">
                        <label for="userActive" class="form-label">Statut</label>
                        <select class="form-control" id="userActive" name="active">
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddUser" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier utilisateur</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditUser">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editUserId" name="id">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editUserName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editUserLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editUserEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserType" class="form-label">Type *</label>
                        <select class="form-control" id="editUserType" name="role" required>
                            <option value="">Sélectionner un type</option>
                            <option value="senior">Senior</option>
                            <option value="prestataire">Prestataire</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editUserPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="editUserPhone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="editUserCity" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="editUserCity" name="city">
                    </div>
                    <div class="mb-3">
                        <label for="editUserActive" class="form-label">Statut</label>
                        <select class="form-control" id="editUserActive" name="active">
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditUser" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewUser(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    alert("Prénom: " + user.first_name + "\nNom: " + user.last_name + "\nEmail: " + user.email + "\nTéléphone: " + (user.phone || 'N/A') + "\nVille: " + (user.city || 'N/A') + "\nRôle: " + user.role);
}

function loadUserData(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editUserId').value = user.id_user;
    document.getElementById('editUserName').value = user.first_name;
    document.getElementById('editUserLastName').value = user.last_name;
    document.getElementById('editUserEmail').value = user.email;
    document.getElementById('editUserType').value = user.role;
    document.getElementById('editUserPhone').value = user.phone || '';
    document.getElementById('editUserCity').value = user.city || '';
    document.getElementById('editUserActive').value = user.active ? '1' : '0';
    openModal('modalEditUser');
}

document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    const tableRows = document.querySelectorAll('#usersTable tbody tr[data-role]');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            filterButtons.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.add('btn-primary', 'active');
            this.classList.remove('btn-outline-primary');
            
            const filter = this.getAttribute('data-filter');
            tableRows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                } else {
                    row.style.display = row.getAttribute('data-role') === filter ? '' : 'none';
                }
            });
        });
    });
});
</script>

<?php
include '../include/footer-admin.php';
?>