<?php
include_once __DIR__ . '/_auth.php';
include 'include/header-prestataire.php';

$message = '';
$messageType = '';
$providerCategories = [];

if ($pdo instanceof PDO && $providerData) {
    try {
        $categoriesStmt = $pdo->prepare(
            "SELECT psc.id_service_category, sc.name
             FROM provider_service_categories psc
             INNER JOIN service_categories sc ON sc.id_service_category = psc.id_service_category
             WHERE psc.id_user = ?
             ORDER BY sc.name ASC"
        );
        $categoriesStmt->execute([(string)$providerData['id_user']]);
        $providerCategories = $categoriesStmt->fetchAll() ?: [];
    } catch (Exception $e) {
        $providerCategories = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providerData && $token !== '') {
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour modifier les disponibilites.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $fromDate = trim((string)($_POST['available_from_date'] ?? ''));
            $toDate = trim((string)($_POST['available_to_date'] ?? ''));
            $start = (string)($_POST['start_time'] ?? '');
            $end = (string)($_POST['end_time'] ?? '');
            $categoryId = trim((string)($_POST['id_service_category'] ?? ''));

            if ($fromDate === '' || $toDate === '' || $start === '' || $end === '' || $categoryId === '') {
                throw new RuntimeException('Tous les champs sont obligatoires.');
            }
            if ($start >= $end) {
                throw new RuntimeException('L heure de fin doit etre apres l heure de debut.');
            }

            $validCategoryIds = array_map(static fn($row) => (string)$row['id_service_category'], $providerCategories);
            if (!in_array($categoryId, $validCategoryIds, true)) {
                throw new RuntimeException('Cette categorie ne fait pas partie de vos expertises.');
            }

            $fromTimestamp = strtotime($fromDate);
            $toTimestamp = strtotime($toDate);
            if ($fromTimestamp === false || $toTimestamp === false) {
                throw new RuntimeException('Plage de dates invalide.');
            }
            if ($toTimestamp < $fromTimestamp) {
                throw new RuntimeException('La date de fin doit etre superieure ou egale a la date de debut.');
            }
            if ($fromDate < date('Y-m-d')) {
                throw new RuntimeException('La date de debut doit etre aujourd hui ou dans le futur.');
            }
            if ($fromDate === date('Y-m-d')) {
                $now = date('H:i:s');
                $startWithSeconds = strlen($start) === 5 ? $start . ':00' : $start;
                $endWithSeconds = strlen($end) === 5 ? $end . ':00' : $end;

                if ($endWithSeconds <= $now) {
                    throw new RuntimeException('Cette plage horaire est deja passee pour aujourd hui.');
                }

                if ($startWithSeconds < $now) {
                    throw new RuntimeException('L heure de debut doit etre dans le futur pour aujourd hui.');
                }
            }

            $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-availabilities', 'POST', [
                'available_from_date' => $fromDate,
                'available_to_date' => $toDate,
                'start_time' => $start,
                'end_time' => $end,
                'id_service_category' => $categoryId,
            ], $token);
            if (!is_array($response) || isset($response['error'])) {
                throw new RuntimeException((string)($response['error'] ?? 'Impossible d ajouter la disponibilite.'));
            }

            $insertedCount = (int)($response['inserted_count'] ?? 1);
            $message = 'Disponibilite ajoutee (' . $insertedCount . ' creneau(x)).';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $availabilityId = (int)($_POST['id_availability'] ?? 0);
            $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-availabilities/' . urlencode((string)$availabilityId), 'DELETE', null, $token);
            if (is_array($response) && isset($response['error'])) {
                throw new RuntimeException((string)$response['error']);
            }
            $message = 'Disponibilite supprimee.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$availabilities = [];
if ($providerData && $token !== '') {
    $response = callAPI('http://localhost:8080/api/users/' . urlencode((string)$providerData['id_user']) . '/provider-availabilities', 'GET', null, $token);
    if (is_array($response) && !isset($response['error'])) {
        $availabilities = $response;
    } else {
        $message = 'Erreur: ' . (string)($response['error'] ?? 'Impossible de charger les disponibilites.');
        $messageType = 'danger';
    }
}

$basePath = '../';
?>

<style>
.provider-avail-table th,
.provider-avail-table td {
    white-space: nowrap;
    vertical-align: middle;
}

.provider-calendar-wrap {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #fff;
    padding: 0.75rem;
}

.provider-calendar-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.provider-cal-nav {
    min-width: 30px;
    height: 30px;
    line-height: 1;
    padding: 0;
}

.provider-cal-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.provider-cal-table th,
.provider-cal-table td {
    border: 1px solid #cfd4da;
    text-align: center;
}

.provider-cal-table th {
    background: #f2f3f5;
    color: #374151;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.45rem 0;
}

.provider-cal-table td {
    height: 62px;
    font-size: 0.95rem;
    color: #111827;
    background: #fff;
    vertical-align: middle;
}

.provider-cal-table td.cal-empty {
    background: #f8f9fa;
}

.provider-cal-table td.cal-has-slot {
    background: #e8f7ee;
    color: #166534;
    font-weight: 600;
}

.provider-cal-table td.cal-today {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}

.provider-cal-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: currentColor;
    margin-left: 0.35rem;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .provider-avail-table thead {
        display: none;
    }

    .provider-avail-table,
    .provider-avail-table tbody,
    .provider-avail-table tr,
    .provider-avail-table td {
        display: block;
        width: 100%;
    }

    .provider-avail-table tr {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        background: #fff;
    }

    .provider-avail-table td {
        border: 0 !important;
        border-bottom: 1px solid #f1f3f5;
        text-align: right;
        padding-left: 45%;
        position: relative;
    }

    .provider-avail-table td:last-child {
        border-bottom: 0;
    }

    .provider-avail-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 0.75rem;
        width: 40%;
        text-align: left;
        font-weight: 600;
        color: #495057;
    }
}
</style>

<div class="page-title h3 mb-3">Mes disponibilites</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>" role="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!$providerData): ?>
<div class="alert alert-warning" role="alert">Aucune fiche prestataire associee.</div>
<?php else: ?>
    <?php if (!$isProviderValidated): ?>
    <div class="alert alert-warning" role="alert">Compte non valide: ajout/modification bloques.</div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="provider-calendar-wrap">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="provider-calendar-title">Mois</div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary provider-cal-nav" id="calPrev">&lt;</button>
                        <button class="btn btn-sm btn-outline-secondary provider-cal-nav" id="calNext">&gt;</button>
                    </div>
                </div>
                <div class="text-muted small mb-2" id="calTitle"></div>
                <table class="provider-cal-table" aria-label="Calendrier des disponibilites">
                    <thead>
                        <tr>
                            <?php foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $d): ?>
                                <th scope="col"><?= $d ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="calGrid"></tbody>
                </table>
            </div>
            <div class="mt-3 d-flex gap-3 align-items-center small text-muted">
                <span><span class="badge bg-success">&nbsp;</span> Disponible</span>
                <span><span class="badge bg-secondary">&nbsp;</span> Aucun créneau</span>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Ajouter une plage</h5>
            <?php if (empty($providerCategories)): ?>
            <div class="alert alert-warning" role="alert">
                Aucune expertise configuree. Mettez a jour votre profil avant d ajouter des disponibilites.
            </div>
            <?php endif; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-3">
                    <label class="form-label">Date debut</label>
                    <input type="date" name="available_from_date" class="form-control" min="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="available_to_date" class="form-control" min="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Debut</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fin</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categorie</label>
                    <select name="id_service_category" class="form-control" required>
                        <option value="">Choisir</option>
                        <?php foreach ($providerCategories as $category): ?>
                            <option value="<?= htmlspecialchars((string)$category['id_service_category']) ?>"><?= htmlspecialchars((string)$category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 d-flex justify-content-end">
                    <button class="btn btn-primary w-100" type="submit" <?= !$isProviderValidated ? 'disabled' : '' ?>>Ajouter</button>
                </div>
            </form>
            <small class="text-muted">Vous pouvez ajouter une plage de dates complete pour une categorie de service.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Liste des disponibilites</h5>
            <div class="table-responsive border rounded">
                <table class="table table-bordered table-striped table-hover mb-0 provider-avail-table">
                    <thead class="table-light"><tr><th>Categorie</th><th>Date</th><th>Debut</th><th>Fin</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($availabilities)): ?>
                        <tr><td colspan="5" class="text-center py-4 fw-semibold">Aucune disponibilite.</td></tr>
                    <?php else: ?>
                        <?php foreach ($availabilities as $row): ?>
                        <tr>
                            <td data-label="Categorie"><?= htmlspecialchars((string)($row['category_name'] ?? (string)($row['id_service_category'] ?? 'N/A'))) ?></td>
                            <td data-label="Date"><?= htmlspecialchars(date('d/m/Y', strtotime((string)$row['available_date']))) ?></td>
                            <td data-label="Debut"><?= htmlspecialchars(substr((string)$row['start_time'], 0, 5)) ?></td>
                            <td data-label="Fin"><?= htmlspecialchars(substr((string)$row['end_time'], 0, 5)) ?></td>
                            <td data-label="Action">
                                <form method="POST" onsubmit="return confirm('Supprimer cette disponibilite ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_availability" value="<?= (int)$row['id_availability'] ?>">
                                    <button class="btn btn-sm btn-outline-danger w-100" <?= !$isProviderValidated ? 'disabled' : '' ?>>Supprimer</button>
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
<?php endif; ?>

<?php include '../include/footer.php'; ?>

<script>
const datesAvec = <?= json_encode(array_values(array_unique(array_map(
    fn($r) => substr((string)($r['available_date'] ?? ''), 0, 10),
    $availabilities
)))) ?>;

const mois = ['Janvier','Février','Mars','Avril','Mai','Juin',
              'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

let annee = new Date().getFullYear();
let moisIdx = new Date().getMonth();

function afficherCalendrier() {
    const grid  = document.getElementById('calGrid');
    const titre = document.getElementById('calTitle');
    titre.textContent = mois[moisIdx] + ' ' + annee;

    const nbJours   = new Date(annee, moisIdx + 1, 0).getDate();
    const premierJS = new Date(annee, moisIdx, 1).getDay();
    const decalage  = premierJS === 0 ? 6 : premierJS - 1;
    const aujourdhui = new Date().toISOString().slice(0, 10);

    let html = '';

    let jour = 1;
    for (let semaine = 0; semaine < 6; semaine++) {
        html += '<tr>';
        for (let col = 0; col < 7; col++) {
            const indexCase = (semaine * 7) + col;
            if (indexCase < decalage || jour > nbJours) {
                html += '<td class="cal-empty"></td>';
            } else {
                const mm = String(moisIdx + 1).padStart(2, '0');
                const jj = String(jour).padStart(2, '0');
                const date = annee + '-' + mm + '-' + jj;
                const dispo = datesAvec.includes(date);
                const estAujourd = date === aujourdhui;

                let classes = '';
                if (dispo) {
                    classes += ' cal-has-slot';
                }
                if (estAujourd) {
                    classes += ' cal-today';
                }

                html += '<td class="' + classes.trim() + '">' + jour + (dispo ? '<span class="provider-cal-dot"></span>' : '') + '</td>';
                jour++;
            }
        }
        html += '</tr>';
    }

    grid.innerHTML = html;
}

document.getElementById('calPrev').onclick = function () {
    moisIdx--;
    if (moisIdx < 0) { moisIdx = 11; annee--; }
    afficherCalendrier();
};

document.getElementById('calNext').onclick = function () {
    moisIdx++;
    if (moisIdx > 11) { moisIdx = 0; annee++; }
    afficherCalendrier();
};

afficherCalendrier();
</script>
