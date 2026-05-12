<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';
require_once __DIR__ . '/../../db.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$prestataires = [];
$serviceCategories = [];
$serviceCategoryNames = [];

$documentsByUser = [];
if ($pdo instanceof PDO) {
    $docRows = $pdo->query(
        "SELECT id_user, document_type, file_name, file_path, uploaded_at FROM provider_documents ORDER BY uploaded_at ASC"
    );
    if ($docRows) {
        foreach ($docRows->fetchAll() as $doc) {
            $documentsByUser[$doc['id_user']][] = $doc;
        }
    }

    $catRows = $pdo->query(
        "SELECT id_service_category, name FROM service_categories ORDER BY name ASC"
    );
    if ($catRows) {
        $serviceCategories = $catRows->fetchAll();
        foreach ($serviceCategories as $cat) {
            $catId = (string)($cat['id_service_category'] ?? '');
            if ($catId !== '') {
                $serviceCategoryNames[$catId] = (string)($cat['name'] ?? '');
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'validate' || $action === 'reject') {
        $newStatus = $action === 'validate' ? 'Valide' : 'Rejete';
        $userId = $_POST['id'] ?? '';
        if ($userId !== '' && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("UPDATE users SET validation_status = :status, active = :active WHERE id_user = :id AND role = 'prestataire'");
            $stmt->execute([
                'status' => $newStatus,
                'active' => $action === 'validate' ? 1 : 0,
                'id'     => $userId,
            ]);
            $message = $action === 'validate' ? 'Prestataire validé.' : 'Prestataire rejeté.';
            $messageType = $action === 'validate' ? 'success' : 'warning';
        }
    }
    
    if ($action === 'create') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'role' => 'prestataire',
            'phone' => $_POST['phone'] ?? null,
            'company_name' => $_POST['company_name'] ?? null,
            'active' => 1
        ];
        
        $response = callAPI('http://silverhappy_api:8080/api/users', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Prestataire ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $expertiseCategoryId = isset($_POST['expertise_category_id']) ? trim((string)$_POST['expertise_category_id']) : '';
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'] ?? null,
            'company_name' => $_POST['company_name'] ?? null,
        ];

        if ($expertiseCategoryId !== '' && isset($serviceCategoryNames[$expertiseCategoryId])) {
            $data['company_name'] = $serviceCategoryNames[$expertiseCategoryId];
        }
        
        $response = callAPI("http://silverhappy_api:8080/api/users/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $userId = $_POST['id'];
            if ($pdo instanceof PDO && $expertiseCategoryId !== '') {
                try {
                    $pdo->prepare("DELETE FROM provider_service_categories WHERE id_user = ?")->execute([(string)$userId]);
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO provider_service_categories (id_user, id_service_category, created_at) VALUES (?, ?, NOW())"
                    );
                    $insertStmt->execute([(string)$userId, $expertiseCategoryId]);
                } catch (Exception $e) {
                }
            }
            $message = "Prestataire modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://silverhappy_api:8080/api/users/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Prestataire supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://silverhappy_api:8080/api/users', 'GET', null, $token);
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
    } elseif (is_array($response)) {
        $prestataires = array_filter($response, function($user) {
            return isset($user['role']) && $user['role'] === 'prestataire';
        });
        $prestataires = array_values($prestataires);

        if ($pdo instanceof PDO && !empty($prestataires)) {
            foreach ($prestataires as &$pres) {
                $catStmt = $pdo->prepare(
                    "SELECT psc.id_service_category, sc.name FROM provider_service_categories psc LEFT JOIN service_categories sc ON sc.id_service_category = psc.id_service_category WHERE psc.id_user = ? LIMIT 1"
                );
                $catStmt->execute([(string)$pres['id_user']]);
                $catResult = $catStmt->fetch();
                $pres['expertise_category_id'] = $catResult ? (string)$catResult['id_service_category'] : '';
                $pres['expertise_category_name'] = $catResult ? (string)($catResult['name'] ?? '') : '';
            }
            unset($pres);
        }
    } else {
        $message = "Format de réponse invalide de l'API.";
        $messageType = "warning";
    }
} else {
    $message = "Token d'authentification manquant.";
    $messageType = "danger";
}
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="page-title">Gestion des Prestataires</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Tous</a>
            <a href="./seniors.php" class="btn btn-sm btn-outline-primary">Seniors</a>
            <a href="./prestataires.php" class="btn btn-sm btn-primary active">Prestataires</a>
            <a href="./administrateurs.php" class="btn btn-sm btn-outline-primary">Administrateurs</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddProvider">+ Ajouter un prestataire</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Spécialité</th>
                    <th>Prestations</th>
                    <th>Validation</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestataires)): ?>
                    <tr><td colspan="7" class="text-center">Aucun prestataire trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($prestataires as $prestataire): ?>
                        <?php
                        $fullName = trim((string)($prestataire['first_name'] ?? '') . ' ' . (string)($prestataire['last_name'] ?? ''));
                        $email = (string)($prestataire['email'] ?? '');
                        $companyName = (string)($prestataire['company_name'] ?? '');
                        $expertiseLabel = (string)($prestataire['expertise_category_name'] ?? '');
                        $specialityDisplay = $expertiseLabel !== '' ? $expertiseLabel : $companyName;
                        $prestationsCount = (int)($prestataire['prestations_count'] ?? 0);
                        $validationStatus = (string)($prestataire['validation_status'] ?? 'En attente');
                        $isValidated = in_array($validationStatus, ['Validé', 'Valide'], true);
                        $isRejected = in_array($validationStatus, ['Rejeté', 'Rejete'], true);
                        $idUser = (string)($prestataire['id_user'] ?? '');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($fullName) ?></td>
                            <td><?= htmlspecialchars($email) ?></td>
                            <td><?= htmlspecialchars($specialityDisplay) ?></td>
                            <td><?= $prestationsCount ?></td>
                            <td>
                                <?php if ($isValidated): ?>
                                    <span class="badge bg-success">Validé</span>
                                <?php elseif ($validationStatus === 'En attente'): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php elseif ($isRejected): ?>
                                    <span class="badge bg-danger">Rejeté</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($validationStatus) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prestataire['active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-user="<?= htmlspecialchars(json_encode($prestataire)) ?>" onclick="viewProvider(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-user="<?= htmlspecialchars(json_encode($prestataire)) ?>" onclick="editProvider(this)"><i class="bi bi-pencil"></i></button>
                                <?php if (!$isValidated): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="validate">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($idUser) ?>">
                                    <button type="submit" class="btn btn-sm btn-success" title="Valider ce prestataire"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (!$isRejected): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($idUser) ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="Rejeter ce prestataire" onclick="return confirm('Rejeter ce prestataire ?')"><i class="bi bi-x-lg"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce prestataire ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($idUser) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="modalViewProvider" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fiche prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Nom :</strong> <span id="vpName"></span></p>
                        <p class="mb-1"><strong>Email :</strong> <span id="vpEmail"></span></p>
                        <p class="mb-1"><strong>Téléphone :</strong> <span id="vpPhone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Raison sociale :</strong> <span id="vpCompany"></span></p>
                        <p class="mb-1"><strong>SIRET :</strong> <span id="vpSiret"></span></p>
                        <p class="mb-1"><strong>Zone :</strong> <span id="vpZone"></span></p>
                    </div>
                </div>
                <h6 class="border-top pt-3">Documents justificatifs</h6>
                <div id="vpDocs"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddProvider" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddProvider">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="providerFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="providerFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="providerLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="providerEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="providerSpecialty" class="form-label">Spécialité/Entreprise</label>
                        <input type="text" class="form-control" id="providerSpecialty" name="company_name">
                    </div>
                    <div class="mb-3">
                        <label for="providerPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="providerPhone" name="phone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddProvider" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditProvider" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier prestataire</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditProvider">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editProviderId" name="id">
                    <div class="mb-3">
                        <label for="editProviderFirstName" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="editProviderFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderLastName" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="editProviderLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editProviderEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderPhone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="editProviderPhone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="editProviderCompany" class="form-label">Profession</label>
                        <input type="text" class="form-control" id="editProviderCompany" name="company_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderSkills" class="form-label">Domaine d'expertise</label>
                        <select class="form-control" id="editProviderSkills" name="expertise_category_id">
                            <option value="">-- Sélectionner un domaine --</option>
                            <?php foreach ($serviceCategories as $cat): ?>
                                <option value="<?= htmlspecialchars((string)$cat['id_service_category']) ?>">
                                    <?= htmlspecialchars((string)$cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editProviderSiret" class="form-label">SIRET</label>
                        <input type="text" class="form-control" id="editProviderSiret" name="siret_number" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditProvider" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
const documentsByUser = <?= json_encode($documentsByUser) ?>;
const serviceCategoryNames = <?= json_encode($serviceCategoryNames) ?>;

const docLabels = {
    casier_judiciaire: 'Casier judiciaire (B3)',
    diplome: 'Diplôme / Certification',
    recommandation: 'Lettre de recommandation'
};

function viewProvider(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));

    document.getElementById('vpName').textContent    = user.first_name + ' ' + user.last_name;
    document.getElementById('vpEmail').textContent   = user.email || '—';
    document.getElementById('vpPhone').textContent   = user.phone || '—';
    document.getElementById('vpCompany').textContent = user.company_name || '—';
    document.getElementById('vpSiret').textContent   = user.siret_number || '—';
    document.getElementById('vpZone').textContent    = user.zone || '—';

    const docs = documentsByUser[user.id_user] || [];
    const vpDocs = document.getElementById('vpDocs');

    if (docs.length === 0) {
        vpDocs.innerHTML = '<p class="text-muted">Aucun document envoyé.</p>';
    } else {
        vpDocs.innerHTML = docs.map(function(doc) {
            const label = docLabels[doc.document_type] || doc.document_type;
            const href = '/thib/Silver-Happy/' + doc.file_path;
            return '<div class="d-flex align-items-center gap-2 mb-2">'
                + '<i class="bi bi-file-earmark-text text-primary fs-5"></i>'
                + '<strong>' + label + '</strong>'
                + '<a href="' + href + '" target="_blank" class="btn btn-sm btn-outline-primary ms-auto">'
                + '<i class="bi bi-download"></i> Voir</a>'
                + '</div>';
        }).join('');
    }

    openModal('modalViewProvider');
}

function editProvider(btn) {
    const user = JSON.parse(btn.getAttribute('data-user'));
    document.getElementById('editProviderId').value = user.id_user;
    document.getElementById('editProviderFirstName').value = user.first_name;
    document.getElementById('editProviderLastName').value = user.last_name;
    document.getElementById('editProviderEmail').value = user.email;
    document.getElementById('editProviderPhone').value = user.phone || '';
    document.getElementById('editProviderSkills').value = user.expertise_category_id || '';
    document.getElementById('editProviderCompany').value = serviceCategoryNames[user.expertise_category_id] || user.company_name || '';
    document.getElementById('editProviderSiret').value = user.siret_number || '';
    openModal('modalEditProvider');
}

document.addEventListener('DOMContentLoaded', function () {
    const expertiseSelect = document.getElementById('editProviderSkills');
    const companyInput = document.getElementById('editProviderCompany');
    if (!expertiseSelect || !companyInput) {
        return;
    }

    expertiseSelect.addEventListener('change', function () {
        const selectedCategoryId = expertiseSelect.value || '';
        companyInput.value = selectedCategoryId !== '' && serviceCategoryNames[selectedCategoryId]
            ? serviceCategoryNames[selectedCategoryId]
            : '';
    });
});
</script>

<?php
include '../include/footer-admin.php';
?>