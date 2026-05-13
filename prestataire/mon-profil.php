<?php
include_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../db.php';
include 'include/header-prestataire.php';

$providerDocuments = [];
if ($pdo instanceof PDO && isset($user['id_user'])) {
    $docStmt = $pdo->prepare(
        'SELECT document_type, file_name, file_path, uploaded_at FROM provider_documents WHERE id_user = :id_user ORDER BY uploaded_at ASC'
    );
    $docStmt->execute(['id_user' => $user['id_user']]);
    $providerDocuments = $docStmt->fetchAll();
}

$message = $_SESSION['provider_profile_message'] ?? '';
$messageType = $_SESSION['provider_profile_message_type'] ?? '';
unset($_SESSION['provider_profile_message'], $_SESSION['provider_profile_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $providerData && $token !== '') {
    try {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $zone = trim((string)($_POST['zone'] ?? ''));
        $iban = trim((string)($_POST['iban'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $skills = trim((string)($_POST['skills_text'] ?? ''));

        $response = callAPI('http://silverhappy_api:8080/api/users/' . urlencode((string)$providerData['id_user']), 'PATCH', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone !== '' ? $phone : null,
            'company_name' => $companyName !== '' ? $companyName : null,
            'zone' => $zone !== '' ? $zone : null,
            'iban' => $iban !== '' ? $iban : null,
            'provider_description' => $description !== '' ? $description : null,
            'skills_text' => $skills !== '' ? $skills : null,
            'provider_updated_at' => date('Y-m-d H:i:s'),
        ], $token);
        if (!is_array($response) || isset($response['error'])) {
            throw new RuntimeException((string)($response['error'] ?? 'Impossible de mettre a jour le profil.'));
        }

        $_SESSION['provider_profile_message'] = 'Profil mis a jour.';
        $_SESSION['provider_profile_message_type'] = 'success';

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
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$phoneValue = (string)($currentUserData['phone'] ?? '');
$basePath = '../';
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
<?php
$docLabels = ['casier_judiciaire' => 'Casier judiciaire (B3)', 'diplome' => 'Diplôme', 'recommandation' => 'Recommandation'];
?>
<div class="card mt-3">
    <div class="card-body py-2 px-3">
        <div class="small fw-semibold mb-2">Documents d'inscription</div>
        <?php if (!empty($providerDocuments)): ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($providerDocuments as $doc): ?>
            <li class="d-flex align-items-center gap-2 py-1 border-bottom">
                <span class="small text-muted" style="min-width:160px"><?= $docLabels[$doc['document_type']] ?? htmlspecialchars($doc['document_type']) ?></span>
                <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="small text-truncate"><?= htmlspecialchars($doc['file_name']) ?></a>
                <span class="small text-muted ms-auto text-nowrap"><?= htmlspecialchars($doc['uploaded_at']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="small text-muted mb-0">Aucun document transmis lors de l'inscription.</p>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php include '../include/footer.php'; ?>
