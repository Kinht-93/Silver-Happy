<?php
include '../include/header-admin.php';

$message = '';
$messageType = '';
$token = $_SESSION['user']['token'] ?? '';
$contenus = [];

function callAPI($url, $method = 'GET', $data = null, $token = '') {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "X-Token: {$token}\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Impossible de se connecter à l\'API'];
    }
    
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'title' => $_POST['title'],
            'category' => $_POST['category'],
            'content_body' => $_POST['body'],
            'status' => $_POST['status'] ?? 'Brouillon'
        ];
        
        $response = callAPI('http://localhost:8080/api/contents', 'POST', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Contenu ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de l'ajout.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $data = [
            'title' => $_POST['title'],
            'category' => $_POST['category'],
            'content_body' => $_POST['body'],
            'status' => $_POST['status'] ?? 'Brouillon'
        ];
        
        $response = callAPI("http://localhost:8080/api/contents/{$_POST['id']}", 'PATCH', $data, $token);
        if ($response && isset($response['Message']) && !isset($response['error'])) {
            $message = "Contenu modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $response = callAPI("http://localhost:8080/api/contents/{$_POST['id']}", 'DELETE', null, $token);
        $message = "Contenu supprimé.";
        $messageType = "success";
    }
}

if (!empty($token)) {
    $response = callAPI('http://localhost:8080/api/contents', 'GET', null, $token);
    
    if (isset($response['error'])) {
        $message = "Erreur API: " . $response['error'];
        $messageType = "danger";
        $contenus = [];
    } elseif (is_array($response)) {
        $contenus = $response;
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

<div class="page-title">Gestion des contenus</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-primary active">Conseils</a>
            <a href="./langues.php" class="btn btn-sm btn-outline-primary">Langues & traductions</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddContent">+ Ajouter un conseil</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Auteur</th>
                    <th>Date création</th>
                    <th>Vues</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contenus)): ?>
                    <tr><td colspan="7" class="text-center">Aucun contenu trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($contenus as $contenu): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($contenu['title']) ?></strong></td>
                            <td><?= htmlspecialchars($contenu['category']) ?></td>
                            <td><?= htmlspecialchars(($contenu['first_name'] ?? '') . ' ' . ($contenu['last_name'] ?? '')) ?></td>
                            <td><?= date('d/m/Y', strtotime($contenu['created_at'])) ?></td>
                            <td><?= (int)($contenu['views'] ?? 0) ?></td>
                            <td>
                                <?php if ($contenu['status'] == 'Publié'): ?>
                                    <span class="badge bg-success">Publié</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= htmlspecialchars($contenu['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-content="<?= htmlspecialchars(json_encode($contenu)) ?>" onclick="viewContent(this)"><i class="bi bi-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-content="<?= htmlspecialchars(json_encode($contenu)) ?>" onclick="editContent(this)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce contenu ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($contenu['id_content']) ?>">
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

<div class="modal fade" id="modalAddContent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un conseil</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formAddContent">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="contentTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="contentTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="contentCategory" class="form-label">Catégorie *</label>
                        <select class="form-control" id="contentCategory" name="category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option value="Santé">Santé</option>
                            <option value="Nutrition">Nutrition</option>
                            <option value="Exercice">Exercice</option>
                            <option value="Bien-être">Bien-être</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="contentBody" class="form-label">Contenu *</label>
                        <textarea class="form-control" id="contentBody" name="body" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="contentStatus" class="form-label">Statut</label>
                        <select class="form-control" id="contentStatus" name="status">
                            <option value="Brouillon">Brouillon</option>
                            <option value="Publié">Publié</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formAddContent" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditContent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier contenu</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditContent">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editContentId" name="id">
                    <div class="mb-3">
                        <label for="editContentTitle" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="editContentTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContentCategory" class="form-label">Catégorie *</label>
                        <select class="form-control" id="editContentCategory" name="category" required>
                            <option value="Santé">Santé</option>
                            <option value="Nutrition">Nutrition</option>
                            <option value="Exercice">Exercice</option>
                            <option value="Bien-être">Bien-être</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editContentBody" class="form-label">Contenu *</label>
                        <textarea class="form-control" id="editContentBody" name="body" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editContentStatus" class="form-label">Statut</label>
                        <select class="form-control" id="editContentStatus" name="status">
                            <option value="Brouillon">Brouillon</option>
                            <option value="Publié">Publié</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" form="formEditContent" class="btn btn-primary">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewContent(btn) {
    const contentData = JSON.parse(btn.getAttribute('data-content'));
    alert('Contenu: ' + contentData.title + '\nCatégorie: ' + contentData.category + '\nStatut: ' + contentData.status + '\nContenu: ' + (contentData.content_body || ''));
}

function editContent(btn) {
    const contentData = JSON.parse(btn.getAttribute('data-content'));
    document.getElementById('editContentId').value = contentData.id_content;
    document.getElementById('editContentTitle').value = contentData.title || '';
    document.getElementById('editContentCategory').value = contentData.category || '';
    document.getElementById('editContentBody').value = contentData.content_body || '';
    document.getElementById('editContentStatus').value = contentData.status || 'Brouillon';
    openModal('modalEditContent');
}
</script>

<?php
include '../include/footer-admin.php';
?>
