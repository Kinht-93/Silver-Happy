<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

if (!function_exists('sh_resolve_category_from_api')) {
    function sh_resolve_category_from_api($categories, $categoryId, $categoryName)
    {
        $categoryId = trim((string)$categoryId);
        $categoryName = trim((string)$categoryName);

        foreach ($categories as $category) {
            if ($categoryId !== '' && (string)($category['id_service_category'] ?? '') === $categoryId) {
                return $category;
            }
            if ($categoryName !== '' && strcasecmp((string)($category['name'] ?? ''), $categoryName) === 0) {
                return $category;
            }
        }

        return null;
    }
}

$seniorCurrent = 'prestation';
$message = '';
$messageType = '';
$availableSlots = [];
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');
$seniorFullName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
$categoryOptions = [];

$selectedCategoryId = trim((string)($_GET['category_id'] ?? $_POST['category_id'] ?? ''));
$selectedCategoryName = trim((string)($_GET['category_name'] ?? $_POST['category_name'] ?? ''));
$selectedCategory = null;

if ($token !== '') {
    $categoriesResponse = callAPI('http://localhost:8080/api/service-categories', 'GET', null, $token);
    if (is_array($categoriesResponse) && !isset($categoriesResponse['error'])) {
        $categoryOptions = $categoriesResponse;
        $selectedCategory = sh_resolve_category_from_api($categoryOptions, $selectedCategoryId, $selectedCategoryName);
        if ($selectedCategory) {
            $selectedCategoryId = (string)$selectedCategory['id_service_category'];
            $selectedCategoryName = (string)$selectedCategory['name'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'reserve') {
        $availabilityId = (int)($_POST['id_availability'] ?? 0);

        if ($availabilityId <= 0 || $userId === '') {
            $message = 'Reservation invalide.';
            $messageType = 'danger';
        } elseif ($selectedCategoryId === '' || !$selectedCategory) {
            $message = 'Veuillez choisir un type de service valide.';
            $messageType = 'danger';
        } else {
            $response = callAPI('http://localhost:8080/api/provider-availabilities/' . urlencode((string)$availabilityId) . '/reserve', 'POST', [
                'id_user' => $userId,
                'id_service_category' => $selectedCategoryId,
                'category_name' => (string)$selectedCategory['name'],
                'senior_name' => $seniorFullName !== '' ? $seniorFullName : $userId,
            ], $token);

            if (is_array($response) && !isset($response['error'])) {
                header('Location: planning.php?reserved=1');
                exit;
            }

            $message = $response['error'] ?? 'Impossible de reserver ce creneau.';
            $messageType = 'danger';
        }
    }
}

if ($token !== '') {
    $slotsResponse = callAPI('http://localhost:8080/api/provider-availabilities', 'GET', null, $token);
    if (is_array($slotsResponse) && !isset($slotsResponse['error'])) {
        $availableSlots = array_values(array_filter($slotsResponse, static function ($slot) {
            return !empty($slot['available_date']) && !empty($slot['start_time']) && !empty($slot['end_time']);
        }));
        usort($availableSlots, static function ($left, $right) {
            $leftKey = (string)($left['available_date'] ?? '') . ' ' . (string)($left['start_time'] ?? '');
            $rightKey = (string)($right['available_date'] ?? '') . ' ' . (string)($right['start_time'] ?? '');
            return strcmp($leftKey, $rightKey);
        });
    } elseif ($message === '') {
        $message = 'Impossible de charger les disponibilites des prestataires.';
        $messageType = 'danger';
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Creneaux disponibles</h1>
            <p class="senier-subtitle">
                Service choisi:
                <strong><?= htmlspecialchars((string)($selectedCategory['name'] ?? 'Non defini')) ?></strong>
            </p>
        </div>
        <div class="senier-breadcrumb">Accueil/Prestations/Creneaux</div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType !== '' ? $messageType : 'info') ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$selectedCategory): ?>
        <div class="alert alert-warning" role="alert">
            Type de service invalide. <a href="prestations-catalogue.php">Retourner a la liste des services</a>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Prestataires et disponibilites</h5>
                    <a class="btn btn-outline-secondary btn-sm" href="prestations-catalogue.php">Changer de service</a>
                </div>

                <?php if (empty($availableSlots)): ?>
                    <p class="mb-0">Aucun creneau disponible pour le moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Prestataire</th>
                                    <th>Entreprise</th>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableSlots as $slot): ?>
                                    <?php $providerName = trim((string)(($slot['first_name'] ?? '') . ' ' . ($slot['last_name'] ?? ''))); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($providerName !== '' ? $providerName : 'Prestataire') ?></td>
                                        <td><?= htmlspecialchars((string)($slot['company_name'] ?? 'N/A')) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string)$slot['available_date']))) ?></td>
                                        <td><?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Confirmer la reservation de ce creneau ?');">
                                                <input type="hidden" name="action" value="reserve">
                                                <input type="hidden" name="id_availability" value="<?= (int)$slot['id_availability'] ?>">
                                                <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$selectedCategory['id_service_category']) ?>">
                                                <input type="hidden" name="category_name" value="<?= htmlspecialchars((string)$selectedCategory['name']) ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Reserver</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php include './include/footer.php'; ?>
