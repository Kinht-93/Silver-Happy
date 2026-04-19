<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'prestation';
$serviceCategories = [];
$loadError = '';
$token = (string)($_SESSION['user']['token'] ?? '');


if ($token !== '') {
    $serviceMap = [];

    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories', 'GET', null, $token);
    if (is_array($categoriesResponse) && !isset($categoriesResponse['error'])) {
        foreach ($categoriesResponse as $apiCategory) {
            $apiName = (string)($apiCategory['name'] ?? '');
            if ($apiName === '') {
                continue;
            }

            $key = strtolower($apiName);
            $apiDescription = trim((string)($apiCategory['description'] ?? ''));
            if ($apiDescription === '' || strtolower($apiDescription) === 'categorie de service') {
                $apiDescription = '';
            }

            if (isset($serviceMap[$key])) {
                $serviceMap[$key]['id_service_category'] = (string)($apiCategory['id_service_category'] ?? $serviceMap[$key]['id_service_category']);
                if ($apiDescription !== '') {
                    $serviceMap[$key]['description'] = $apiDescription;
                }
            } else {
                $serviceMap[$key] = [
                    'id_service_category' => (string)($apiCategory['id_service_category'] ?? ''),
                    'name' => $apiName,
                    'description' => $apiDescription !== '' ? $apiDescription : 'Service disponible.',
                ];
            }
        }

        $serviceCategories = array_values($serviceMap);
    } else {
        $loadError = 'Impossible de charger les types de service.';
        $serviceCategories = $defaultServiceCategories;
    }
} else {
    $serviceCategories = $defaultServiceCategories;
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Prestations</h1>
            <p class="senier-subtitle">Liste des besoins adaptés à chaque résident.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Prestations</div>
    </div>

    <?php if ($loadError): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($loadError) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Types de services</h5>
            <?php if (empty($serviceCategories)): ?>
                <p class="mb-0">Aucun type de service disponible pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceCategories as $category): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$category['name']) ?></td>
                                    <td><?= htmlspecialchars((string)($category['description'] ?? '')) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-primary" href="prestations-creneaux.php?category_id=<?= urlencode((string)$category['id_service_category']) ?>&category_name=<?= urlencode((string)$category['name']) ?>">Voir les creneaux</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</section>

<?php include './include/footer.php'; ?>
