<?php
include_once __DIR__ . '/_auth.php';
include 'include/header-prestataire.php';

require_once __DIR__ . '/../db.php';

$avis = [];
if ($providerData && $pdo instanceof PDO) {
    $stmt = $pdo->prepare(
        "SELECT r.rating, r.comment, r.created_at,
                u.first_name, u.last_name
         FROM reviews r
         LEFT JOIN users u ON u.id_user = r.id_user
         WHERE r.id_reviewed = :id AND r.visible = 1
         ORDER BY COALESCE(r.created_at, r.review_date) DESC"
    );
    $stmt->execute(['id' => $providerData['id_user']]);
    $avis = $stmt->fetchAll();
}

$moyennne = 0;
if (!empty($avis)) {
    $moyennne = round(array_sum(array_column($avis, 'rating')) / count($avis), 1);
}

$basePath = '../';
?>

<div class="page-title h3 mb-3">Mes avis</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if (empty($avis)): ?>
    <div class="alert alert-info">Vous n'avez pas encore reçu d'avis.</div>
<?php else: ?>

    <div class="card mb-4">
        <div class="card-body text-center">
            <div class="fs-1 fw-bold text-warning"><?= $moyennne ?> / 5</div>
            <div class="text-muted"><?= count($avis) ?> avis reçu<?= count($avis) > 1 ? 's' : '' ?></div>
            <div class="mt-1">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="fs-4 <?= $i <= round($moyennne) ? 'text-warning' : 'text-secondary' ?>">★</span>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <?php foreach ($avis as $a): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></strong>
                <span class="text-muted small"><?= htmlspecialchars(date('d/m/Y', strtotime($a['created_at']))) ?></span>
            </div>
            <div class="mb-1">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="<?= $i <= $a['rating'] ? 'text-warning' : 'text-secondary' ?>">★</span>
                <?php endfor; ?>
            </div>
            <p class="mb-0 text-muted"><?= htmlspecialchars($a['comment'] ?? '') ?></p>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php include '../include/footer.php'; ?>
