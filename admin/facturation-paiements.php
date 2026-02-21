<?php
include './include/header-admin.php';
?>

<div class="page-title">Facturation & paiements</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-label">Chiffre d'affaires</div>
            <div class="stat-value">24,850€</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Paiements reçus</div>
            <div class="stat-value">215</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">En attente</div>
            <div class="stat-value">8</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">En retard</div>
            <div class="stat-value">3</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card p-4">
            <h5 class="mb-3">Factures récentes</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>#FAC-2026-0045</strong></td>
                            <td>Marie Dupont</td>
                            <td>74,75€</td>
                            <td>19/02/2026</td>
                            <td>19/03/2026</td>
                            <td><span class="badge bg-success">Payée</span></td>
                            <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td>
                        </tr>
                        <tr>
                            <td><strong>#FAC-2026-0046</strong></td>
                            <td>Jean Martin</td>
                            <td>12,00€</td>
                            <td>18/02/2026</td>
                            <td>18/03/2026</td>
                            <td><span class="badge bg-success">Payée</span></td>
                            <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td>
                        </tr>
                        <tr>
                            <td><strong>#FAC-2026-0047</strong></td>
                            <td>Fatima Ahmed</td>
                            <td>129,00€</td>
                            <td>17/02/2026</td>
                            <td>17/03/2026</td>
                            <td><span class="badge bg-warning">En attente</span></td>
                            <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td>
                        </tr>
                        <tr>
                            <td><strong>#FAC-2026-0048</strong></td>
                            <td>Michèle Bernard</td>
                            <td>39,00€</td>
                            <td>16/02/2026</td>
                            <td>16/03/2026</td>
                            <td><span class="badge bg-danger">En retard</span></td>
                            <td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4">
            <h5 class="mb-3">Moyens de paiement acceptés</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-credit-card"></i> Carte bancaire</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-paypal"></i> PayPal</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bank"></i> Virement bancaire</span>
                    <input type="checkbox" class="form-check-input" checked>
                </div>
                <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-phone"></i> Paiement au tél</span>
                    <input type="checkbox" class="form-check-input">
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
