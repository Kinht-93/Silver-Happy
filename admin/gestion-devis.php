<?php
include './include/header-admin.php';
?>

<div class="page-title">Gestion des devis</div>

<div class="row mb-4">
    <div class="col">
        <input type="text" class="form-control" style="max-width: 250px;" placeholder="Rechercher un devis...">
    </div>
    <div class="col text-end">
        <button class="btn btn-sm btn-success" data-modal="modalAddQuote">+ Créer un devis</button>
    </div>

</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Devis</th>
                    <th>Senior</th>
                    <th>Prestation</th>
                    <th>Montant</th>
                    <th>Date création</th>
                    <th>Date validité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>#DV-2026-001</strong></td>
                    <td>Marie Dupont</td>
                    <td>Aide à domicile - 10 séances</td>
                    <td>299,00€</td>
                    <td>15/02/2026</td>
                    <td>15/03/2026</td>
                    <td><span class="badge bg-warning">En attente</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#DV-2026-002</strong></td>
                    <td>Jean Martin</td>
                    <td>Coaching bien-être - 5 séances</td>
                    <td>195,00€</td>
                    <td>17/02/2026</td>
                    <td>17/03/2026</td>
                    <td><span class="badge bg-success">Accepté</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#DV-2026-003</strong></td>
                    <td>Fatima Ahmed</td>
                    <td>Ateliers créatifs - 8 séances</td>
                    <td>96,00€</td>
                    <td>18/02/2026</td>
                    <td>18/03/2026</td>
                    <td><span class="badge bg-danger">Refusé</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>#DV-2026-004</strong></td>
                    <td>Michèle Bernard</td>
                    <td>Cours informatique - 6 séances</td>
                    <td>90,00€</td>
                    <td>19/02/2026</td>
                    <td>19/03/2026</td>
                    <td><span class="badge bg-warning">En attente</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="modalAddQuote" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer un devis</h5>
                <button type="button" class="btn-close" data-modal-close></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="quoteSenior" class="form-label">Senior</label>
                        <select class="form-control" id="quoteSenior">
                            <option>Sélectionner un senior</option>
                            <option>Marie Dupont</option>
                            <option>Jean Martin</option>
                            <option>Fatima Ahmed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quoteService" class="form-label">Prestation</label>
                        <select class="form-control" id="quoteService">
                            <option>Sélectionner une prestation</option>
                            <option>Aide à domicile</option>
                            <option>Coaching bien-être</option>
                            <option>Cours informatique</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quoteAmount" class="form-label">Montant</label>
                        <input type="number" class="form-control" id="quoteAmount" step="0.01">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="button" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
