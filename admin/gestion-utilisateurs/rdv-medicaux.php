<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$user_id = $_SESSION['user']['id_user'] ?? '';
$appointments = [];
$users = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'id_user' => $_POST['id_user'],
            'appointment_date' => $_POST['appointment_date'],
            'appointment_type' => $_POST['appointment_type'] ?? 'Visite médicale',
            'doctor_name' => $_POST['doctor_name'] ?? '',
            'notes_internal' => $_POST['notes_internal'] ?? '',
            'created_by' => $user_id
        ];
        
        $response = callAPI('http://localhost:8080/api/medical-appointments', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "RDV médical créé avec succès.";
            $messageType = "success";
        } else {
            $message = $response['error'] ?? "Erreur lors de la création du RDV.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'appointment_date' => $_POST['appointment_date'],
            'appointment_type' => $_POST['appointment_type'],
            'status' => $_POST['status']
        ];
        
        $response = callAPI("http://localhost:8080/api/medical-appointments/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "RDV médical modifié.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification du RDV.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/medical-appointments/{$_POST['id']}", 'DELETE', null, $token);
        $message = "RDV médical supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/medical-appointments', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $appointments = [];
    } elseif (is_array($response)) {
        $appointments = $response;
    }

    $usersResponse = callAPI('http://localhost:8080/api/users-for-appointments', 'GET', null, $token);
    if (is_array($usersResponse) && !isset($usersResponse['error'])) {
        $users = $usersResponse;
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

<div class="page-title">Gestion des RDV Médicaux</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> 
    <strong>Confidentialité:</strong> Les détails médicaux sont anonymisés pour les employés. 
    Seuls les administrateurs voient les notes internes complètes.
</div>

<div class="row mb-4">
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddAppointment">+ Ajouter un RDV</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Personne</th>
                    <th>Date RDV</th>
                    <th>Type</th>
                    <th>Médecin</th>
                    <th>Statut</th>
                    <th>Créé par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="7" class="text-center">Aucun RDV médical.</td></tr>
                <?php else: ?>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td><?= htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
                            <td><?= htmlspecialchars($apt['appointment_type'] ?? 'Visite médicale') ?></td>
                            <td><?= htmlspecialchars($apt['doctor_name'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $apt['status'] === 'Programmé' ? 'info' : 'success' ?>"><?= htmlspecialchars($apt['status']) ?></span></td>
                            <td><?= htmlspecialchars(($apt['creator_first_name'] ?? 'Admin') . ' ' . ($apt['creator_last_name'] ?? '')) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewAppointment(<?= htmlspecialchars(json_encode($apt)) ?>)" title="Voir détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editAppointment(<?= htmlspecialchars(json_encode($apt)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($apt['id_appointment']) ?>">
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

<div class="modal fade" id="modalViewAppointment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails RDV Médical</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Personne:</strong> <span id="viewPersonName"></span></p>
                <p><strong>Date:</strong> <span id="viewDate"></span></p>
                <p><strong>Type:</strong> <span id="viewType"></span></p>
                <p><strong>Médecin:</strong> <span id="viewDoctor"></span></p>
                <p><strong>Statut:</strong> <span id="viewStatus"></span></p>
                <p><strong>Notes (Admin):</strong></p>
                <pre id="viewNotes" style="background: #f5f5f5; padding: 10px; border-radius: 4px;"></pre>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddAppointment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un RDV médical</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddAppointment">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Personne *</label>
                        <select class="form-control" name="id_user" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['id_user']) ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date et heure *</label>
                        <input type="datetime-local" class="form-control" name="appointment_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de visite</label>
                        <input type="text" class="form-control" name="appointment_type" placeholder="Visite médicale, Dentiste, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Médecin</label>
                        <input type="text" class="form-control" name="doctor_name" placeholder="Nom du médecin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes internes (Admin)</label>
                        <textarea class="form-control" name="notes_internal" rows="3" placeholder="Motif médical, détails confidentiels..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditAppointment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditAppointment">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editAptId">
                    <div class="mb-3">
                        <label class="form-label">Date et heure</label>
                        <input type="datetime-local" class="form-control" name="appointment_date" id="editAptDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control" name="appointment_type" id="editAptType">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select class="form-control" name="status" id="editAptStatus" required>
                            <option value="Programmé">Programmé</option>
                            <option value="Réalisé">Réalisé</option>
                            <option value="Annulé">Annulé</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function viewAppointment(apt) {
    document.getElementById('viewPersonName').textContent = apt.first_name + ' ' + apt.last_name;
    document.getElementById('viewDate').textContent = new Date(apt.appointment_date).toLocaleString('fr-FR');
    document.getElementById('viewType').textContent = apt.appointment_type || 'Visite médicale';
    document.getElementById('viewDoctor').textContent = apt.doctor_name || '-';
    document.getElementById('viewStatus').textContent = apt.status;
    document.getElementById('viewNotes').textContent = apt.notes_internal || '(Aucune note)';
    new bootstrap.Modal(document.getElementById('modalViewAppointment')).show();
}

function editAppointment(apt) {
    document.getElementById('editAptId').value = apt.id_appointment;
    const date = new Date(apt.appointment_date);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    document.getElementById('editAptDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('editAptType').value = apt.appointment_type || '';
    document.getElementById('editAptStatus').value = apt.status;
    new bootstrap.Modal(document.getElementById('modalEditAppointment')).show();
}
</script>

<?php
include '../include/footer-admin.php';
?>
