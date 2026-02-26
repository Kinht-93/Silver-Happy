<?php
include './include/header-admin.php';
?>

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
                <tr>
                    <td><span class="badge bg-info">Info</span></td>
                    <td>Notification de maintenance</td>
                    <td>19/02/2026 10:30</td>
                    <td>Tous les utilisateurs (234)</td>
                    <td><span class="badge bg-success">Envoyée</span></td>
                    <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td>
                </tr>
                <tr>
                    <td><span class="badge bg-success">Success</span></td>
                    <td>Nouvelle fonctionnalité disponible</td>
                    <td>18/02/2026 14:15</td>
                    <td>Administrateurs (4)</td>
                    <td><span class="badge bg-success">Envoyée</span></td>
                    <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td>
                </tr>
                <tr>
                    <td><span class="badge bg-warning">Warning</span></td>
                    <td>Alerte de performance</td>
                    <td>17/02/2026 09:20</td>
                    <td>Équipe IT (3)</td>
                    <td><span class="badge bg-success">Envoyée</span></td>
                    <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td>
                </tr>
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
                <form>
                    <div class="mb-3">
                        <label for="notificationType" class="form-label">Type de notification</label>
                        <select class="form-control" id="notificationType">
                            <option>Sélectionner un type</option>
                            <option>Info</option>
                            <option>Success</option>
                            <option>Warning</option>
                            <option>Danger/Erreur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notificationTitle" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="notificationTitle" placeholder="Ex: Maintenance prévue">
                    </div>
                    <div class="mb-3">
                        <label for="notificationMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="notificationMessage" rows="4" placeholder="Détails de la notification..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notificationRecipients" class="form-label">Destinataires</label>
                        <select class="form-control" id="notificationRecipients">
                            <option>Sélectionner les destinataires</option>
                            <option>Tous les utilisateurs</option>
                            <option>Administrateurs uniquement</option>
                            <option>Équipe IT</option>
                            <option>Prestataires</option>
                            <option>Seniors</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notificationSchedule" class="form-label">Programmer l'envoi</label>
                        <input type="datetime-local" class="form-control" id="notificationSchedule">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="button" class="btn btn-primary">Créer et envoyer</button>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
