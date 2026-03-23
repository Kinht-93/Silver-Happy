<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO && $providerData) {
    try {
        if (!$isProviderValidated) {
            throw new RuntimeException('Validation administrateur requise pour modifier les disponibilites.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $day = (int)($_POST['available_day'] ?? 0);
            $month = (int)($_POST['available_month'] ?? 0);
            $start = (string)($_POST['start_time'] ?? '');
            $end = (string)($_POST['end_time'] ?? '');

            if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $start === '' || $end === '') {
                throw new RuntimeException('Tous les champs sont obligatoires.');
            }
            if ($start >= $end) {
                throw new RuntimeException('L heure de fin doit etre apres l heure de debut.');
            }

            $year = (int)date('Y');
            if (!checkdate($month, $day, $year)) {
                throw new RuntimeException('Date invalide pour l annee en cours.');
            }

            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if ($date < date('Y-m-d')) {
                throw new RuntimeException('La disponibilite doit etre aujourd hui ou dans le futur.');
            }

            if ($date === date('Y-m-d')) {
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

            $stmt = $pdo->prepare('INSERT INTO provider_availabilities (id_user, available_date, start_time, end_time, is_available, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
            $stmt->execute([$providerData['id_user'], $date, $start, $end]);
            $message = 'Disponibilite ajoutee.';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM provider_availabilities WHERE id_availability = ? AND id_user = ?');
            $stmt->execute([(int)($_POST['id_availability'] ?? 0), $providerData['id_user']]);
            $message = 'Disponibilite supprimee.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$availabilities = [];
if ($pdo instanceof PDO && $providerData) {
    try {
        $stmt = $pdo->prepare('SELECT id_availability, available_date, start_time, end_time FROM provider_availabilities WHERE id_user = ? ORDER BY available_date DESC, start_time DESC');
        $stmt->execute([$providerData['id_user']]);
        $availabilities = $stmt->fetchAll();
    } catch (PDOException $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$basePath = '../';
include '../include/header.php';
?>

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
            <h5 class="mb-3">Ajouter une plage</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-2">
                    <label class="form-label">Jour</label>
                    <input type="number" name="available_day" class="form-control" min="1" max="31" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mois</label>
                    <select name="available_month" class="form-control" required>
                        <option value="">Choisir</option>
                        <option value="1">Janvier</option>
                        <option value="2">Fevrier</option>
                        <option value="3">Mars</option>
                        <option value="4">Avril</option>
                        <option value="5">Mai</option>
                        <option value="6">Juin</option>
                        <option value="7">Juillet</option>
                        <option value="8">Aout</option>
                        <option value="9">Septembre</option>
                        <option value="10">Octobre</option>
                        <option value="11">Novembre</option>
                        <option value="12">Decembre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Debut</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fin</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit" <?= !$isProviderValidated ? 'disabled' : '' ?>>Ajouter</button>
                </div>
            </form>
            <small class="text-muted">L annee est automatiquement l annee courante.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Liste des disponibilites</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Debut</th><th>Fin</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($availabilities)): ?>
                        <tr><td colspan="4" class="text-center">Aucune disponibilite.</td></tr>
                    <?php else: ?>
                        <?php foreach ($availabilities as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m', strtotime((string)$row['available_date']))) ?></td>
                            <td><?= htmlspecialchars(substr((string)$row['start_time'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars(substr((string)$row['end_time'], 0, 5)) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Supprimer cette disponibilite ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_availability" value="<?= (int)$row['id_availability'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" <?= !$isProviderValidated ? 'disabled' : '' ?>>Supprimer</button>
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
