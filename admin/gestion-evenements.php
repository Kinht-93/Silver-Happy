<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO events (id_event, title, event_type, start_date, end_date, max_places, price) VALUES (?, ?, ?, ?, DATE_ADD(?, INTERVAL 2 HOUR), ?, ?)");
            $stmt->execute([
                uniqid('evt_'),
                $_POST['title'],
                $_POST['event_type'] ?? 'Autre',
                $_POST['start_date'],
                $_POST['start_date'],
                $_POST['max_places'] ?: 20,
                $_POST['price'] ?: 0
            ]);
            $message = "Événement créé avec succès.";
            $messageType = "success";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE events SET title=?, start_date=?, max_places=?, price=? WHERE id_event=?");
            $stmt->execute([
                $_POST['title'],
                $_POST['start_date'],
                $_POST['max_places'] ?: 20,
                $_POST['price'] ?: 0,
                $_POST['id']
            ]);
            $message = "Événement modifié avec succès.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmtDb = $pdo->prepare("DELETE FROM events WHERE id_event=?");
            $stmtDb->execute([$_POST['id']]);
            $message = "Événement supprimé.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT e.id_event, e.title, e.start_date, e.event_type, e.max_places, e.price,
           (SELECT COUNT(*) FROM event_registrations er WHERE er.id_event = e.id_event) as participants
    FROM events e
    ORDER BY e.start_date DESC
";
$events = $pdo->query($query)->fetchAll();
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des événements</div>

<div class="row mb-4">
    <div class="col">
        <input type="text" class="form-control" style="max-width: 250px;" placeholder="Rechercher un événement...">
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddEvent">+ Créer un événement</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Localisation</th>
                    <th>Organisateur</th>
                    <th>Participants</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="7" class="text-center">Aucun événement trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($event['title']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></td>
                            <td><i class="bi bi-geo-alt"></i> Non défini</td>
                            <td>Non défini</td>
                            <td><?= (int)$event['participants'] ?> / <?= (int)$event['max_places'] ?></td>
                            <td>
                                <?php if ($event['start_date'] > date('Y-m-d H:i:s')): ?>
                                    <span class="badge bg-warning">Planification</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Complété</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-event="<?= htmlspecialchars(json_encode($event)) ?>" onclick="viewEvent(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-event="<?= htmlspecialchars(json_encode($event)) ?>" onclick="editEvent(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($event['id_event']) ?>">
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

<div class="modal fade" id="modalAddEvent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer un événement</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddEvent">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="eventTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventDate" class="form-label">Date & Heure *</label>
                        <input type="datetime-local" class="form-control" id="eventDate" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventType" class="form-label">Type d'événement</label>
                        <select class="form-control" id="eventType" name="event_type">
                            <option value="Atelier">Atelier</option>
                            <option value="Conférence">Conférence</option>
                            <option value="Sortie">Sortie</option>
                            <option value="Réunion">Réunion</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="eventMaxPlaces" class="form-label">Nombre de places</label>
                        <input type="number" class="form-control" id="eventMaxPlaces" name="max_places" value="20">
                    </div>
                    <div class="mb-3">
                        <label for="eventPrice" class="form-label">Prix (€)</label>
                        <input type="number" class="form-control" id="eventPrice" name="price" step="0.01" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="eventDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddEvent" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditEvent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier événement</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditEvent">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editEventId" name="id">
                    <div class="mb-3">
                        <label for="editEventTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="editEventTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEventDate" class="form-label">Date & Heure *</label>
                        <input type="datetime-local" class="form-control" id="editEventDate" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEventMaxPlaces" class="form-label">Nombre de places</label>
                        <input type="number" class="form-control" id="editEventMaxPlaces" name="max_places">
                    </div>
                    <div class="mb-3">
                        <label for="editEventPrice" class="form-label">Prix (€)</label>
                        <input type="number" class="form-control" id="editEventPrice" name="price" step="0.01">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditEvent" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewEvent(btn) {
    const eventData = JSON.parse(btn.getAttribute('data-event'));
    alert('Événement: ' + eventData.title + '\nDate: ' + eventData.start_date + '\nType: ' + eventData.event_type + '\nPlaces max: ' + eventData.max_places + '\nPrix: ' + eventData.price + ' €');
}

function editEvent(btn) {
    const eventData = JSON.parse(btn.getAttribute('data-event'));
    document.getElementById('editEventId').value = eventData.id_event;
    document.getElementById('editEventTitle').value = eventData.title;
    if (eventData.start_date) {
        let date = new Date(eventData.start_date.replace(' ', 'T'));
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        document.getElementById('editEventDate').value = date.toISOString().slice(0, 16);
    }
    document.getElementById('editEventMaxPlaces').value = eventData.max_places || 20;
    document.getElementById('editEventPrice').value = eventData.price || 0;
    openModal('modalEditEvent');
}
</script>

<?php
include './include/footer-admin.php';
?>
