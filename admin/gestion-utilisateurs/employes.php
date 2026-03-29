<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$employes = [];

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
            'phone' => $_POST['phone'] ?? null,
            'role' => 'employee',
            'password' => password_hash('default123', PASSWORD_DEFAULT),
            'active' => 1
        ];
        
        $response = callAPI('http://localhost:8080/api/users', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Employé ajouté avec succès.";
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
            'phone' => $_POST['phone'] ?? null
        ];
        
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Employé modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/users/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Employé supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/employees', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $employes = [];
    } elseif (is_array($response)) {
        $employes = $response;
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

<div class="page-title">Gestion des Employés</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-outline-primary">Prestataires</a>
            <a href="./employes.php" class="btn btn-sm btn-primary active">Employés</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddEmployee">+ Ajouter un employé</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Poste</th>
                    <th>Département</th>
                    <th>Statut contrat</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employes)): ?>
                    <tr><td colspan="7" class="text-center">Aucun employé trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($employes as $employe): ?>
                        <tr>
                            <td><?= htmlspecialchars($employe['first_name'] . ' ' . $employe['last_name']) ?></td>
                            <td><?= htmlspecialchars($employe['email']) ?></td>
                            <td>Non défini</td>
                            <td>Non défini</td>
                            <td><span class="badge bg-secondary">ND</span></td>
                            <td>
                                <?php if ($employe['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($employe)) ?>" onclick="viewEmployee(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($employe)) ?>" onclick="editEmployee(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($employe['id_user']) ?>">
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

<div class="modal fade" id="modalAddEmployee" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un employé</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddEmployee">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="employeeFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="employeeFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="employeeLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="employeeEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeePosition" class="form-label">Poste</label>
                        <input type="text" class="form-control" id="employeePosition" name="position">
                    </div>
                    <div class="mb-3">
                        <label for="employeeDepartment" class="form-label">Département</label>
                        <select class="form-control" id="employeeDepartment" name="department">
                            <option value="">Sélectionner un département</option>
                            <option value="RH">Ressources Humaines</option>
                            <option value="IT">IT</option>
                            <option value="Opérations">Opérations</option>
                            <option value="Support">Support</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddEmployee" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditEmployee" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier employé</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditEmployee">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editEmployeeId" name="id">
                    <div class="mb-3">
                        <label for="editEmployeeFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editEmployeeFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editEmployeeLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editEmployeeEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeePhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="editEmployeePhone" name="phone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditEmployee" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewEmployee(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    alert('Employé: ' + user.first_name + ' ' + user.last_name + '\nEmail: ' + user.email + '\Téléphone: ' + (user.phone || 'N/A'));
}

function editEmployee(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editEmployeeId').value = user.id_user || '';
    document.getElementById('editEmployeeFirstName').value = user.first_name || '';
    document.getElementById('editEmployeeLastName').value = user.last_name || '';
    document.getElementById('editEmployeeEmail').value = user.email || '';
    document.getElementById('editEmployeePhone').value = user.phone || '';
    openModal('modalEditEmployee');
}
</script>

<?php
include '../include/footer-admin.php';
?>
