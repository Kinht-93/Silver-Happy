<?php
include '../include/header-admin.php';

$token = $_SESSION['user']['token'] ?? '';
$realisees = [];

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

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/completed-services-admin', 'GET', null, $token);
    
    if (!isset($response['error']) && is_array($response)) {
        $realisees = $response;
    }
}
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
                            <td><?= htmlspecialchars($realisee['service_name'] ?? 'Non défini') ?></td>
                            <td><?= htmlspecialchars(($realisee['senior_first'] ?? '') . ' ' . ($realisee['senior_last'] ?? '')) ?></td>
                            <td>Prestataire (Non assigné)</td>
                            <td><?= date('d/m/Y', strtotime($realisee['service_date'])) ?></td>
                            <td>
                                <?php 
                                    $duration = $realisee['duration'] ?? 0;
                                    echo round($duration / 60, 1) . 'h';
                                ?>
                            </td>
                            <td><?= number_format($realisee['senior_amount'] ?? 0, 2) ?>€</td>
                            <td>
                                <?php if ($realisee['status'] == 'Complétée'): ?>
                                    <span class="badge bg-success">Complétée</span>
                                <?php elseif ($realisee['status'] == 'En cours'): ?>
                                    <span class="badge bg-warning">En cours</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($realisee['status'] ?? '') ?></span>
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
    document.getElementById('viewServiceName').textContent = service.service_name || 'Non défini';
    document.getElementById('viewSeniorName').textContent = (service.senior_first || '') + ' ' + (service.senior_last || '');
    document.getElementById('viewServiceDate').textContent = new Date(service.service_date).toLocaleDateString('fr-FR');

    const duration = service.duration || 0;
    document.getElementById('viewServiceDuration').textContent = (duration / 60).toFixed(1) + 'h';

    document.getElementById('viewServicePrice').textContent = parseFloat(service.senior_amount || 0).toFixed(2) + '€';
    document.getElementById('viewServiceStatus').textContent = service.status || '';
    openModal('modalViewCompletedService');
}
</script>

<?php
include '../include/footer-admin.php';
?>