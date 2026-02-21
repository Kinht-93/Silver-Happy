<?php
include '../include/header-admin.php';
?>

<div class="page-title">Traductions et langues</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <a href="./index.php" class="btn btn-sm btn-outline-primary">Conseils</a>
            <a href="./langues.php" class="btn btn-sm btn-primary active">Langues & traductions</a>
        </div>
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success">+ Gérer traductions</button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h5 class="mb-4">Langues activées</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">🇫🇷 Français</div>
                        <small class="text-muted">Langue par défaut</small>
                    </div>
                    <input type="checkbox" class="form-check-input" checked disabled>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">🇬🇧 Anglais</div>
                        <small class="text-muted">95% traduit</small>
                    </div>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">🇪🇸 Espagnol</div>
                        <small class="text-muted">78% traduit</small>
                    </div>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">🇩🇪 Allemand</div>
                        <small class="text-muted">52% traduit</small>
                    </div>
                    <input type="checkbox" class="form-check-input">
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">🇮🇹 Italien</div>
                        <small class="text-muted">61% traduit</small>
                    </div>
                    <input type="checkbox" class="form-check-input">
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card p-4">
            <h5 class="mb-4">Statistiques de traduction</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Total de chaînes à traduire</span>
                    <strong>342</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Chaînes traduites (FR)</span>
                    <strong>342 (100%)</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Chaînes traduites (EN)</span>
                    <strong>325 (95%)</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Chaînes traduites (ES)</span>
                    <strong>267 (78%)</strong>
                </div>
                <div class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                    <span>Requêtes de traduction en attente</span>
                    <strong class="text-warning">7</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <h5 class="mb-3">Contenus nécessitant traduction</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Contenu</th>
                    <th>Langue source</th>
                    <th>EN</th>
                    <th>ES</th>
                    <th>DE</th>
                    <th>IT</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10 conseils pour une retraite active</td>
                    <td>Français</td>
                    <td><span class="badge bg-success">✓</span></td>
                    <td><span class="badge bg-success">✓</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><button class="btn btn-sm btn-outline-secondary">Traductions</button></td>
                </tr>
                <tr>
                    <td>Nutrition équilibrée après 60 ans</td>
                    <td>Français</td>
                    <td><span class="badge bg-success">✓</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><button class="btn btn-sm btn-outline-secondary">Traductions</button></td>
                </tr>
                <tr>
                    <td>Les bienfaits des loisirs créatifs</td>
                    <td>Français</td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><span class="badge bg-danger">✗</span></td>
                    <td><button class="btn btn-sm btn-outline-secondary">Traductions</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../include/footer-admin.php';
?>
