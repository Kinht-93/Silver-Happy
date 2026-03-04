<?php
include './include/header-admin.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_notification, type, title, message, created_at, is_read, id_user)
                VALUES (?, ?, ?, ?, NOW(), 0, ?)
            ");

            $recipientType = $_POST['recipients'] ?? 'all';
            $details = $_POST['message'];
            if ($recipientType !== 'all') {
                $details .= "\n\n[Destinataires: " . $recipientType . "]";
            }

            $stmt->execute([
                uniqid('not_'),
                $_POST['type'],
                $_POST['title'],
                $details,
                null
            ]);

            $message = "Notification créée et enregistrée.";
            $messageType = "success";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id_notification = ?");
            $stmt->execute([$_POST['id'] ?? '']);
            $message = "Notification supprimée.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "danger";
    }
}

$query = "
    SELECT n.id_notification, n.type, n.title, n.message, n.created_at, n.is_read,
           u.first_name, u.last_name
    FROM notifications n
    LEFT JOIN users u ON n.id_user = u.id_user
    ORDER BY n.created_at DESC
";
try {
    $notifications = $pdo ? $pdo->query($query)->fetchAll() : [];
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
    $messageType = "danger";
    $notifications = [];
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Notifications système</div>

<div class="row mb-4">
    <div class="col">
        <input type="text" class="form-control" style="max-width: 250px;" placeholder="Rechercher une notification...">
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalCreateNotification">+ Créer une notification</button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="admin-card p-4">
            <h5 class="mb-4">Notifications actives</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><i class="bi bi-exclamation-triangle text-warning"></i> Maintenance prévue</div>
                            <small class="text-muted">20/02/2026 de 22h à 23h</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><i class="bi bi-info-circle text-info"></i> Nouvelle fonctionnalité</div>
                            <small class="text-muted">Gestion des devis disponible</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><i class="bi bi-check-circle text-success"></i> Mise à jour système</div>
                            <small class="text-muted">Version 2.1.0 déployée</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-card p-4">
            <h5 class="mb-4">Alertes système</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold text-danger"><i class="bi bi-exclamation-circle"></i> Stockage disque faible</div>
                            <small class="text-muted">85% utilisé</small>
                        </div>
                        <span class="badge bg-danger">Critique</span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold text-warning"><i class="bi bi-exclamation-triangle"></i> Taux erreurs élevé</div>
                            <small class="text-muted">2,3% des requêtes</small>
                        </div>
                        <span class="badge bg-warning">Avertissement</span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><i class="bi bi-info-circle"></i> Sauvegarde programmée</div>
                            <small class="text-muted">Chaque jour à 02h00</small>
                        </div>
                        <span class="badge bg-info">Info</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <h5 class="mb-3">Historique des notifications</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Date d'envoi</th>
                    <th>Destinataires</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr><td colspan="6" class="text-center">Aucune notification trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td>
                                <?php if ($notification['type'] == 'Info'): ?>
                                    <span class="badge bg-info">Info</span>
                                <?php elseif ($notification['type'] == 'Success'): ?>
                                    <span class="badge bg-success">Success</span>
                                <?php elseif ($notification['type'] == 'Warning'): ?>
                                    <span class="badge bg-warning">Warning</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= htmlspecialchars($notification['type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($notification['title'] ? $notification['title'] : 'Notification sans titre') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></td>
                            <td><?= $notification['first_name'] ? htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) : 'Tous les utilisateurs' ?></td>
                            <td><span class="badge bg-success">Envoyée</span></td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-notification="<?= htmlspecialchars(json_encode($notification)) ?>"
                                    onclick="viewNotification(this)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette notification ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($notification['id_notification']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCreateNotification" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une notification</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form id="formAddNotification" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="notificationType" class="form-label">Type de notification</label>
                        <select class="form-control" id="notificationType" name="type" required>
                            <option value="">Sélectionner un type</option>
                            <option value="Info">Info</option>
                            <option value="Success">Success</option>
                            <option value="Warning">Warning</option>
                            <option value="Danger">Danger/Erreur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notificationTitle" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="notificationTitle" name="title" placeholder="Ex: Maintenance prévue" required>
                    </div>
                    <div class="mb-3">
                        <label for="notificationMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="notificationMessage" name="message" rows="4" placeholder="Détails de la notification..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notificationRecipients" class="form-label">Destinataires</label>
                        <select class="form-control" id="notificationRecipients" name="recipients" required>
                            <option value="">Sélectionner les destinataires</option>
                            <option value="all">Tous les utilisateurs</option>
                            <option value="admin">Administrateurs uniquement</option>
                            <option value="it">Équipe IT</option>
                            <option value="provider">Prestataires</option>
                            <option value="senior">Seniors</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notificationSchedule" class="form-label">Programmer l'envoi</label>
                        <input type="datetime-local" class="form-control" id="notificationSchedule" name="scheduled_at">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddNotification" class="btn btn-primary">Créer et envoyer</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewNotification(btn) {
    const notification = JSON.parse(btn.getAttribute('data-notification'));
    const title = notification.title || 'Notification';
    const msg = notification.message || '';
    alert(title + '\n\n' + msg);
}
</script>

<?php
include './include/footer-admin.php';
?>
