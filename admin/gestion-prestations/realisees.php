<?php
include '../include/header-admin.php';

$query = "
    SELECT cs.id_completed_service, cs.service_date, cs.start_time, cs.end_time, cs.senior_amount, cs.status,
           u_senior.first_name as senior_first, u_senior.last_name as senior_last,
           st.name as prestation_name
    FROM completed_services cs
    JOIN service_requests sr ON cs.id_request = sr.id_request
    JOIN users u_senior ON sr.id_user = u_senior.id_user
    LEFT JOIN show_type sht ON sr.id_request = sht.id_request
    LEFT JOIN service_types st ON sht.id_service_type = st.id_service_type
    ORDER BY cs.service_date DESC
";
$realisees = $pdo->query($query)->fetchAll();
?>

<div class="page-title">Prestations réalisées</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Prestations</a>
            <a href="./categories.php" class="btn btn-sm btn-outline-primary">Catégories</a>
            <a href="./types.php" class="btn btn-sm btn-outline-primary">Types</a>
            <a href="./realisees.php" class="btn btn-sm btn-primary active">Réalisées</a>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Prestation</th>
                    <th>Senior</th>
                    <th>Prestataire</th>
                    <th>Date</th>
                    <th>Durée</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Avis</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($realisees)): ?>
                    <tr><td colspan="9" class="text-center">Aucune prestation réalisée trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($realisees as $realisee): ?>
                        <tr>
                            <td><?= htmlspecialchars($realisee['prestation_name'] ?: 'Non défini') ?></td>
                            <td><?= htmlspecialchars($realisee['senior_first'] . ' ' . $realisee['senior_last']) ?></td>
                            <td>Prestataire (Non assigné)</td>
                            <td><?= date('d/m/Y', strtotime($realisee['service_date'])) ?></td>
                            <td>
                                <?php 
                                    $start = strtotime($realisee['start_time']);
                                    $end = strtotime($realisee['end_time']);
                                    $duration = ($end - $start) / 3600;
                                    echo round($duration, 1) . 'h';
                                ?>
                            </td>
                            <td><?= number_format($realisee['senior_amount'], 2) ?>€</td>
                            <td>
                                <?php if ($realisee['status'] == 'Complétée'): ?>
                                    <span class="badge bg-success">Complétée</span>
                                <?php elseif ($realisee['status'] == 'En cours'): ?>
                                    <span class="badge bg-warning">En cours</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($realisee['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>-</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-service="<?= htmlspecialchars(json_encode($realisee)) ?>"
                                    onclick="viewCompletedService(this)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalViewCompletedService" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la prestation réalisée</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Prestation:</strong> <span id="viewServiceName"></span></p>
                        <p><strong>Senior:</strong> <span id="viewSeniorName"></span></p>
                        <p><strong>Date:</strong> <span id="viewServiceDate"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Durée:</strong> <span id="viewServiceDuration"></span></p>
                        <p><strong>Prix:</strong> <span id="viewServicePrice"></span></p>
                        <p><strong>Statut:</strong> <span id="viewServiceStatus"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewCompletedService(btn) {
    const service = JSON.parse(btn.getAttribute('data-service'));
    document.getElementById('viewServiceName').textContent = service.prestation_name || 'Non défini';
    document.getElementById('viewSeniorName').textContent = service.senior_first + ' ' + service.senior_last;
    document.getElementById('viewServiceDate').textContent = new Date(service.service_date).toLocaleDateString('fr-FR');

    const start = new Date(service.service_date + ' ' + service.start_time);
    const end = new Date(service.service_date + ' ' + service.end_time);
    const duration = (end - start) / (1000 * 60 * 60);
    document.getElementById('viewServiceDuration').textContent = duration.toFixed(1) + 'h';

    document.getElementById('viewServicePrice').textContent = parseFloat(service.senior_amount).toFixed(2) + '€';
    document.getElementById('viewServiceStatus').textContent = service.status;
    openModal('modalViewCompletedService');
}
</script>

<?php
include '../include/footer-admin.php';
?>