<?php
include './include/header-admin.php';
require_once __DIR__ . '/../include/callapi.php';

$token = $_SESSION['user']['token'] ?? '';
$filterType = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';

function getSortUrl($column, $currentSort, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query($params);
}

$queryParams = [];
if ($filterType) $queryParams[] = 'type=' . urlencode($filterType);
if ($search) $queryParams[] = 'search=' . urlencode($search);
if ($sort) $queryParams[] = 'sort=' . urlencode($sort);
if ($order) $queryParams[] = 'order=' . urlencode($order);
$queryString = !empty($queryParams) ? '?' . implode('&', $queryParams) : '';

$response = callAPI('http://localhost:8080/api/logs' . $queryString, 'GET', null, $token);
$logs = is_array($response) && !isset($response['error']) ? $response : [];

$stats = [
    'total' => count($logs),
    'success' => count(array_filter($logs, fn($l) => $l['statut'] === true)),
    'errors' => count(array_filter($logs, fn($l) => $l['statut'] === false)),
];
?>

<div class="page-title">Logs & supervision</div>

<!-- Barre de recherche -->
<div class="row mb-4">
    <div class="col-md-8">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Rechercher dans les logs..." value="<?= htmlspecialchars($search) ?>">
            <?php if ($filterType): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-search"></i> Rechercher
            </button>
            <?php if ($search || $filterType): ?>
                <a href="logs.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Effacer
                </a>
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <small class="text-muted">
            <?= count($logs) ?> résultat<?= count($logs) > 1 ? 's' : '' ?> trouvé<?= count($logs) > 1 ? 's' : '' ?>
            <?php if ($search): ?>
                pour "<strong><?= htmlspecialchars($search) ?></strong>"
            <?php endif; ?>
        </small>
    </div>
</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filterType === '' ? 'active' : '' ?>" href="?<?= $search ? 'search=' . urlencode($search) : '' ?><?= $search && ($sort !== 'created_at' || $order !== 'desc') ? '&' : '' ?><?= $sort !== 'created_at' || $order !== 'desc' ? 'sort=' . urlencode($sort) . '&order=' . urlencode($order) : '' ?>">Tous les logs</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filterType === 'ERROR' ? 'active' : '' ?>" href="?type=ERROR<?= $search ? '&search=' . urlencode($search) : '' ?><?= ($search || $sort !== 'created_at' || $order !== 'desc') ? '&' : '' ?><?= $sort !== 'created_at' || $order !== 'desc' ? 'sort=' . urlencode($sort) . '&order=' . urlencode($order) : '' ?>">Erreurs</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filterType === 'LOGIN' ? 'active' : '' ?>" href="?type=LOGIN<?= $search ? '&search=' . urlencode($search) : '' ?><?= ($search || $sort !== 'created_at' || $order !== 'desc') ? '&' : '' ?><?= $sort !== 'created_at' || $order !== 'desc' ? 'sort=' . urlencode($sort) . '&order=' . urlencode($order) : '' ?>">Accès</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= in_array($filterType, ['CREATE', 'UPDATE', 'DELETE']) ? 'active' : '' ?>" href="?type=UPDATE<?= $search ? '&search=' . urlencode($search) : '' ?><?= ($search || $sort !== 'created_at' || $order !== 'desc') ? '&' : '' ?><?= $sort !== 'created_at' || $order !== 'desc' ? 'sort=' . urlencode($sort) . '&order=' . urlencode($order) : '' ?>">Modifications</a>
    </li>
</ul>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Succès</div>
            <div class="stat-value"><?= $stats['success'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">Erreurs</div>
            <div class="stat-value"><?= $stats['errors'] ?></div>
        </div>
    </div>
</div>

<div class="admin-card">
    <h5 class="mb-3">Historique des activités</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <a href="<?= getSortUrl('created_at', $sort, $order) ?>" class="text-decoration-none text-dark">
                            Timestamp
                        </a>
                    </th>
                    <th>
                        <a href="<?= getSortUrl('utilisateur', $sort, $order) ?>" class="text-decoration-none text-dark">
                            Utilisateur
                        </a>
                    </th>
                    <th>
                        <a href="<?= getSortUrl('action', $sort, $order) ?>" class="text-decoration-none text-dark">
                            Action
                        </a>
                    </th>
                    <th>
                        <a href="<?= getSortUrl('type', $sort, $order) ?>" class="text-decoration-none text-dark">
                            Type
                        </a>
                    </th>
                    <th>Détails</th>
                    <th>
                        <a href="<?= getSortUrl('statut', $sort, $order) ?>" class="text-decoration-none text-dark">
                            Statut 
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucun log disponible.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['utilisateur'] ?? 'Système') ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td>
                                <?php
                                    $type = htmlspecialchars($log['type']);
                                    $badgeClass = match($type) {
                                        'CREATE' => 'bg-info',
                                        'UPDATE' => 'bg-warning',
                                        'DELETE' => 'bg-danger',
                                        'LOGIN' => 'bg-light text-dark',
                                        'LOGOUT' => 'bg-light text-dark',
                                        'ERROR' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $type ?></span>
                            </td>
                            <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                            <td>
                                <?php if ($log['statut']): ?>
                                    <span class="badge bg-success">Succès</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Échoué</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include './include/footer-admin.php'; ?>
