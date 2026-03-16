<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $year = date('Y');
            $lastNumber = $pdo->prepare("SELECT ticket_number FROM support_tickets WHERE ticket_number LIKE ? ORDER BY ticket_number DESC LIMIT 1");
            $like = "TKT-$year-%";
            $lastNumber->execute([$like]);
            $last = $lastNumber->fetchColumn();
            $seq = 1;
            if ($last && preg_match('#TKT-' . $year . '-(\d{4})#', $last, $m)) {
                $seq = (int)$m[1] + 1;
            }
            $ticketNumber = sprintf('TKT-%s-%04d', $year, $seq);
            
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets 
                (id_ticket, ticket_number, title, description, category, priority, status, id_user, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                uniqid('tkt_'),
                $ticketNumber,
                $_POST['title'],
                $_POST['description'],
                $_POST['category'] ?? 'Autre',
                $_POST['priority'] ?? 'Moyen',
                'Ouvert',
                $_POST['id_user']
            ]);
            $message = "Ticket créé avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE support_tickets 
                SET title=?, description=?, priority=?, status=?, assigned_to=?, updated_at=NOW()
                WHERE id_ticket=?
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['priority'],
                $_POST['status'],
                !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
                $_POST['id']
            ]);
            $message = "Ticket modifié.";
            $messageType = "success";
        } elseif ($action === 'resolve') {
            $stmt = $pdo->prepare("
                UPDATE support_tickets 
                SET status='Fermé', resolved_at=NOW(), resolution_notes=?, updated_at=NOW()
                WHERE id_ticket=?
            ");
            $stmt->execute([
                $_POST['resolution_notes'] ?? '',
                $_POST['id']
            ]);
            $message = "Ticket fermé.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id_ticket=?");
            $stmt->execute([$_POST['id']]);
            $message = "Ticket supprimé.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$filterStatus = $_GET['status'] ?? 'tous';
$query = "
    SELECT st.id_ticket, st.ticket_number, st.title, st.description, st.category, st.priority, st.status,
           st.id_user, st.assigned_to, st.created_at, st.updated_at, st.resolved_at, st.resolution_notes,
           u.first_name, u.last_name, u.email,
           a.first_name as assigned_first, a.last_name as assigned_last
    FROM support_tickets st
    INNER JOIN users u ON st.id_user = u.id_user
    LEFT JOIN users a ON st.assigned_to = a.id_user
";

if ($filterStatus !== 'tous') {
    $query .= " WHERE st.status = ?";
    $params = [$filterStatus];
} else {
    $params = [];
}

$query .= " ORDER BY st.priority DESC, st.created_at DESC";

try {
    if (!empty($params)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $tickets = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $tickets = [];
    $message = "Erreur BD: " . $e->getMessage();
    $messageType = "danger";
}

try {
    $admins = $pdo->query("
        SELECT id_user, first_name, last_name 
        FROM users 
        WHERE active = TRUE
        ORDER BY last_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admins = [];
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des Tickets & Problèmes</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="?status=tous" class="btn btn-sm <?= ($filterStatus === 'tous' ? 'btn-primary' : 'btn-outline-primary') ?>">Tous</a>
            <a href="?status=Ouvert" class="btn btn-sm <?= ($filterStatus === 'Ouvert' ? 'btn-primary' : 'btn-outline-primary') ?>">Ouverts</a>
            <a href="?status=Assigné" class="btn btn-sm <?= ($filterStatus === 'Assigné' ? 'btn-primary' : 'btn-outline-primary') ?>">Assignés</a>
            <a href="?status=Fermé" class="btn btn-sm <?= ($filterStatus === 'Fermé' ? 'btn-primary' : 'btn-outline-primary') ?>">Fermés</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddTicket">+ Nouveau ticket</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Titre</th>
                    <th>Auteur</th>
                    <th>Catégorie</th>
                    <th>Priorité</th>
                    <th>Statut</th>
                    <th>Assigné à</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="9" class="text-center">Aucun ticket.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ticket['title']) ?></td>
                            <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['category'] ?? '-') ?></td>
                            <td>
                                <?php
                                $priorityColor = match($ticket['priority']) {
                                    'Critique' => 'danger',
                                    'Haute' => 'warning',
                                    'Moyen' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $priorityColor ?>"><?= htmlspecialchars($ticket['priority']) ?></span>
                            </td>
                            <td>
                                <?php
                                $statusColor = match($ticket['status']) {
                                    'Ouvert' => 'danger',
                                    'Assigné' => 'warning',
                                    'Fermé' => 'success',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($ticket['status']) ?></span>
                            </td>
                            <td><?= htmlspecialchars(($ticket['assigned_first'] ?? 'Non assigné') . ' ' . ($ticket['assigned_last'] ?? '')) ?></td>
                            <td><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewTicket(<?= htmlspecialchars(json_encode($ticket)) ?>)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editTicket(<?= htmlspecialchars(json_encode($ticket)) ?>)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($ticket['id_ticket']) ?>">
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

<div class="modal fade" id="modalViewTicket" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTicketTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Numéro:</strong> <span id="viewTicketNum"></span></p>
                <p><strong>Auteur:</strong> <span id="viewTicketAuthor"></span></p>
                <p><strong>Catégorie:</strong> <span id="viewTicketCategory"></span></p>
                <p><strong>Description:</strong></p>
                <p id="viewTicketDesc" style="background: #f5f5f5; padding: 10px; border-radius: 4px;"></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddTicket" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddTicket">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Utilisateur concerné *</label>
                        <select class="form-control" name="id_user" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= htmlspecialchars($admin['id_user']) ?>">
                                    <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-control" name="category">
                            <option value="Paiement">Paiement</option>
                            <option value="Prestation">Prestation</option>
                            <option value="Utilisateur">Utilisateur</option>
                            <option value="Technique">Technique</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priorité</label>
                        <select class="form-control" name="priority">
                            <option value="Basse">Basse</option>
                            <option selected value="Moyen">Moyen</option>
                            <option value="Haute">Haute</option>
                            <option value="Critique">Critique</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditTicket" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditTicket">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editTicketId">
                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="title" id="editTicketTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editTicketDesc" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priorité</label>
                        <select class="form-control" name="priority" id="editTicketPriority">
                            <option value="Basse">Basse</option>
                            <option value="Moyen">Moyen</option>
                            <option value="Haute">Haute</option>
                            <option value="Critique">Critique</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select class="form-control" name="status" id="editTicketStatus">
                            <option value="Ouvert">Ouvert</option>
                            <option value="Assigné">Assigné</option>
                            <option value="Fermé">Fermé</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assigner à</label>
                        <select class="form-control" name="assigned_to" id="editTicketAssigned">
                            <option value="">Aucun</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= htmlspecialchars($admin['id_user']) ?>">
                                    <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function viewTicket(ticket) {
    document.getElementById('viewTicketTitle').textContent = ticket.ticket_number + ' - ' + ticket.title;
    document.getElementById('viewTicketNum').textContent = ticket.ticket_number;
    document.getElementById('viewTicketAuthor').textContent = ticket.first_name + ' ' + ticket.last_name;
    document.getElementById('viewTicketCategory').textContent = ticket.category || '-';
    document.getElementById('viewTicketDesc').textContent = ticket.description;
    new bootstrap.Modal(document.getElementById('modalViewTicket')).show();
}

function editTicket(ticket) {
    document.getElementById('editTicketId').value = ticket.id_ticket;
    document.getElementById('editTicketTitle').value = ticket.title;
    document.getElementById('editTicketDesc').value = ticket.description;
    document.getElementById('editTicketPriority').value = ticket.priority;
    document.getElementById('editTicketStatus').value = ticket.status;
    document.getElementById('editTicketAssigned').value = ticket.assigned_to || '';
    new bootstrap.Modal(document.getElementById('modalEditTicket')).show();
}
</script>

<?php
include './include/footer-admin.php';
?>
