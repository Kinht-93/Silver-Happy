<?php
$seniorCurrent = 'prestations';
require_once __DIR__ . '/../include/callapi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string)($_SESSION['user']['token'] ?? '');
$categories = [];
$typesByCategory = [];

if ($token !== '') {
    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories', 'GET', null, $token);
    $typesResponse = callAPI('http://localhost:8080/api/service-types', 'GET', null, $token);

    if (
        is_array($categoriesResponse) && !isset($categoriesResponse['error']) &&
        is_array($typesResponse) && !isset($typesResponse['error'])
    ) {
        $categories = $categoriesResponse;
        foreach ($categories as $category) {
            $typesByCategory[(string)($category['id_service_category'] ?? '')] = [];
        }
        foreach ($typesResponse as $type) {
            $categoryId = (string)($type['id_service_category'] ?? '');
            if (!isset($typesByCategory[$categoryId])) {
                $typesByCategory[$categoryId] = [];
            }
            $typesByCategory[$categoryId][] = $type;
        }
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Catalogue des prestations</h1>
        <div class="senior-panel">
            <?php if (empty($categories)): ?>
                <p class="mb-0">Liste des prestations indisponible pour le moment.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryId = (string)($category['id_service_category'] ?? ''); ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100 bg-white">
                                <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                                    <div>
                                        <h2 class="h5 mb-1"><?= htmlspecialchars((string)($category['name'] ?? 'Catégorie')) ?></h2>
                                        <p class="text-muted mb-0"><?= htmlspecialchars((string)($category['description'] ?? '')) ?></p>
                                    </div>
                                    <a class="btn btn-outline-success btn-sm" href="prestations-demande.php?category=<?= urlencode((string)($category['name'] ?? '')) ?>">Demander</a>
                                </div>
                                <?php if (empty($typesByCategory[$categoryId])): ?>
                                    <p class="mb-0 small text-muted">Aucun type de service rattaché.</p>
                                <?php else: ?>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($typesByCategory[$categoryId] as $type): ?>
                                            <li class="mb-2">
                                                <strong><?= htmlspecialchars((string)($type['name'] ?? 'Service')) ?></strong>
                                                <?php if (!empty($type['description'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars((string)$type['description']) ?></small>
                                                <?php endif; ?>
                                                <br><small><?= number_format((float)($type['hourly_rate'] ?? 0), 2) ?> € / heure</small>
                                                <br><a href="prestations-creneaux.php?category_id=<?= urlencode($categoryId) ?>&category_name=<?= urlencode((string)($category['name'] ?? '')) ?>" class="small">Voir les créneaux</a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
