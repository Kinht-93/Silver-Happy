<?php
include_once __DIR__ . '/_auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO && $providerData) {
    try {
        $pdo->beginTransaction();

        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $zone = trim((string)($_POST['zone'] ?? ''));
        $iban = trim((string)($_POST['iban'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $skills = trim((string)($_POST['skills_text'] ?? ''));

        $updateUser = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id_user = ?');
        $updateUser->execute([
            $firstName,
            $lastName,
            $phone !== '' ? $phone : null,
            $user['id_user'] ?? ''
        ]);

        $updateProvider = $pdo->prepare(
            'UPDATE users
             SET company_name = ?,
                 zone = ?,
                 iban = ?,
                 provider_description = ?,
                 skills_text = ?,
                 provider_updated_at = NOW()
             WHERE id_user = ?'
        );
        $updateProvider->execute([
            $companyName !== '' ? $companyName : null,
            $zone !== '' ? $zone : null,
            $iban !== '' ? $iban : null,
            $description !== '' ? $description : null,
            $skills !== '' ? $skills : null,
            $providerData['id_user']
        ]);

        $pdo->commit();

        $message = 'Profil mis a jour.';
        $messageType = 'success';

        $_SESSION['user']['first_name'] = $firstName;
        $_SESSION['user']['last_name'] = $lastName;
        $_SESSION['user']['phone'] = $phone;

        $currentUserData = array_merge($currentUserData, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
        ]);

        $providerData['company_name'] = $companyName;
        $providerProfile = [
            'zone' => $zone,
            'iban' => $iban,
            'description' => $description,
            'skills_text' => $skills,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$phoneValue = (string)($currentUserData['phone'] ?? '');
$basePath = '../';
include '../include/header.php';
?>

<div class="page-title h3 mb-3">Mon profil prestataire</div>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if ($dbPageError): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($dbPageError) ?></div>
<?php endif; ?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>" role="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!$providerData): ?>
<div class="alert alert-warning" role="alert">Aucune fiche prestataire associee a ce compte.</div>
<?php else: ?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="small text-muted">Statut validation</div>
                <div class="h5 mb-0"><?= htmlspecialchars((string)($providerData['validation_status'] ?? 'Inconnu')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="small text-muted">Note moyenne</div>
                <div class="h5 mb-0"><?= htmlspecialchars((string)($providerData['average_rating'] ?? '-')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="small text-muted">Commission</div>
                <div class="h5 mb-0"><?= htmlspecialchars((string)($providerData['commission_rate'] ?? '-')) ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
    <h5 class="mb-3">Informations principales</h5>
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Prenom</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars((string)($currentUserData['first_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nom</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars((string)($currentUserData['last_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Telephone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phoneValue) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Entreprise / specialite</label>
                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars((string)($providerData['company_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Zone d'intervention</label>
                <input type="text" name="zone" class="form-control" value="<?= htmlspecialchars((string)($providerProfile['zone'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">IBAN</label>
                <input type="text" name="iban" class="form-control" value="<?= htmlspecialchars((string)($providerProfile['iban'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string)($providerProfile['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Competences</label>
                <textarea name="skills_text" class="form-control" rows="3" placeholder="Ex: Aide a domicile, courses, accompagnement medical"><?= htmlspecialchars((string)($providerProfile['skills_text'] ?? '')) ?></textarea>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button class="btn btn-primary" type="submit">Enregistrer le profil</button>
        </div>
    </form>
    </div>
</div>
<?php endif; ?>

<?php include '../include/footer.php'; ?>
