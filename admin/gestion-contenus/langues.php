<?php
include '../include/header-admin.php';
require_once __DIR__ . '/../../include/callapi.php';
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
        <button class="btn btn-sm btn-success" data-modal="modalManageTranslations">+ Gérer traductions</button>
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

<div class="modal fade" id="modalManageTranslations" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gérer les traductions</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="translationContent" class="form-label">Contenu à traduire</label>
                        <select class="form-control" id="translationContent">
                            <option>Sélectionner un contenu</option>
                            <option>10 conseils pour une retraite active</option>
                            <option>Nutrition équilibrée après 60 ans</option>
                            <option>Les bienfaits des loisirs créatifs</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="translationLanguage" class="form-label">Langue cible</label>
                        <select class="form-control" id="translationLanguage">
                            <option>Sélectionner une langue</option>
                            <option>🇬🇧 Anglais</option>
                            <option>🇪🇸 Espagnol</option>
                            <option>🇩🇪 Allemand</option>
                            <option>🇮🇹 Italien</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="translationText" class="form-label">Traduction</label>
                        <textarea class="form-control" id="translationText" rows="4" placeholder="Entrez la traduction..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="button" class="btn btn-primary">Ajouter traduction</button>
            </div>
        </div>
    </div>
</div>

<?php
include '../include/footer-admin.php';
?>
