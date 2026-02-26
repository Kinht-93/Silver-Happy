<?php
include './include/header-admin.php';
?>

<div class="page-title">Paramètres plateforme</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group" role="group">
            <button class="btn btn-sm btn-primary active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">Général</button>
            <button class="btn btn-sm btn-outline-primary" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">Email</button>
            <button class="btn btn-sm btn-outline-primary" id="securite-tab" data-bs-toggle="tab" data-bs-target="#securite" type="button" role="tab">Sécurité</button>
            <button class="btn btn-sm btn-outline-primary" id="paiements-tab" data-bs-toggle="tab" data-bs-target="#paiements" type="button" role="tab">Paiements</button>
        </div>
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="admin-card p-4">
            <h5 class="mb-4">Paramètres généraux</h5>
            <form>
                <div class="mb-3">
                    <label for="nom_site" class="form-label">Nom du site</label>
                    <input type="text" class="form-control" id="nom_site" value="Silver Happy">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description du site</label>
                    <textarea class="form-control" id="description" rows="3">Services, loisirs, conseils et produits pour les seniors</textarea>
                </div>
                <div class="mb-3">
                    <label for="url_site" class="form-label">URL du site</label>
                    <input type="url" class="form-control" id="url_site" value="https://www.silverhappy.fr">
                </div>
                <div class="mb-3">
                    <label for="email_contact" class="form-label">Email de contact</label>
                    <input type="email" class="form-control" id="email_contact" value="contact@silverhappy.fr">
                </div>
                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone principal</label>
                    <input type="tel" class="form-control" id="telephone" value="+33 1 23 45 67 89">
                </div>
                <div class="mb-3">
                    <label class="form-label">Fuseau horaire</label>
                    <select class="form-select">
                        <option selected>Europe/Paris (UTC+1)</option>
                        <option>Europe/London (UTC+0)</option>
                        <option>Europe/Brussels (UTC+1)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="email" role="tabpanel">
        <div class="admin-card p-4">
            <h5 class="mb-4">Configuration des emails</h5>
            <form>
                <div class="mb-3">
                    <label for="smtp_host" class="form-label">Serveur SMTP</label>
                    <input type="text" class="form-control" id="smtp_host" value="mail.silverhappy.fr">
                </div>
                <div class="mb-3">
                    <label for="smtp_port" class="form-label">Port SMTP</label>
                    <input type="number" class="form-control" id="smtp_port" value="587">
                </div>
                <div class="mb-3">
                    <label for="smtp_user" class="form-label">Utilisateur SMTP</label>
                    <input type="text" class="form-control" id="smtp_user" value="noreply@silverhappy.fr">
                </div>
                <div class="mb-3">
                    <label for="email_from" class="form-label">Email d'expédition</label>
                    <input type="email" class="form-control" id="email_from" value="noreply@silverhappy.fr">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="tls_check" checked>
                    <label class="form-check-label" for="tls_check">
                        Utiliser TLS
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Tester la connexion</button>
                <button type="submit" class="btn btn-success ms-2">Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="securite" role="tabpanel">
        <div class="admin-card p-4">
            <h5 class="mb-4">Paramètres de sécurité</h5>
            <form>
                <div class="mb-3">
                    <label class="form-label">Authentification</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="2fa" checked>
                        <label class="form-check-label" for="2fa">
                            Authentification à deux facteurs obligatoire pour les admins
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="ssl" checked>
                        <label class="form-check-label" for="ssl">
                            Forcer HTTPS
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="session_timeout" class="form-label">Durée de session (minutes)</label>
                    <input type="number" class="form-control" id="session_timeout" value="60">
                </div>
                <div class="mb-3">
                    <label for="max_login_attempts" class="form-label">Tentatives de connexion max (avant blocage)</label>
                    <input type="number" class="form-control" id="max_login_attempts" value="5">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sauvegardes</label>
                    <select class="form-select">
                        <option selected>Quotidienne</option>
                        <option>Hebdomadaire</option>
                        <option>Mensuelle</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <button type="button" class="btn btn-warning ms-2">Sauvegarder maintenant</button>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="paiements" role="tabpanel">
        <div class="admin-card p-4">
            <h5 class="mb-4">Configuration des paiements</h5>
            <form>
                <div class="mb-3">
                    <label for="stripe_key" class="form-label">Clé publique Stripe</label>
                    <input type="text" class="form-control" id="stripe_key" value="pk_live_***** " disabled>
                </div>
                <div class="mb-3">
                    <label for="paypal_id" class="form-label">ID Client PayPal</label>
                    <input type="text" class="form-control" id="paypal_id" value="AZ*****" disabled>
                </div>
                <div class="mb-3">
                    <label for="devise" class="form-label">Devise par défaut</label>
                    <select class="form-select" id="devise">
                        <option selected>EUR (€)</option>
                        <option>USD ($)</option>
                        <option>GBP (£)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tax_rate" class="form-label">Taux TVA (%)</label>
                    <input type="number" class="form-control" id="tax_rate" value="20" step="0.1">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="invoice_auto" checked>
                    <label class="form-check-label" for="invoice_auto">
                        Générer les factures automatiquement
                    </label>
                </div>
                <button type="submit" class="btn btn-success">Enregistrer</button>
            </form>
        </div>
    </div>
</div>

<?php
include './include/footer-admin.php';
?>
