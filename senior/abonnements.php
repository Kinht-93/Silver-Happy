<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';
$seniorCurrent = 'abonnements';

$userId = (string)($_SESSION['user']['id_user'] ?? '');
$token = (string)($_SESSION['user']['token'] ?? '');
$message = '';
$messageType = '';
$plans = [];
$activeSubscription = null;

if (empty($_SESSION['subscription_csrf'])) {
    $_SESSION['subscription_csrf'] = bin2hex(random_bytes(16));
}
$subscriptionCsrf = (string)$_SESSION['subscription_csrf'];

function getPlanDescription(string $planId, string $planName, ?string $dbDescription): string
{
    if ($dbDescription !== null && trim($dbDescription) !== '') {
        return trim($dbDescription);
    }

    $map = [
        'sub_senior_essentiel' => 'Acces aux services essentiels, support standard et tarifs preferentiels sur une selection d activites.',
        'sub_senior_confort' => 'Inclut l Essentiel, plus des avantages evenements et un accompagnement prioritaire.',
        'sub_senior_premium' => 'Formule complete avec avantages et assistance prioritaire sur l ensemble de la plateforme.',
    ];

    return $map[$planId] ?? ('Formule ' . $planName . ' adaptee aux besoins du quotidien.');
}

// Retour depuis Stripe : on confirme le paiement via l'API Go
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && !empty($_GET['session_id'])) {
    $confirmResp = callAPI(
        'http://silverhappy_api:8080/api/subscriptions/confirm?session_id=' . urlencode($_GET['session_id']),
        'GET', null, $token
    );
    if (isset($confirmResp['error'])) {
        $message = 'Erreur confirmation paiement : ' . $confirmResp['error'];
        $messageType = 'danger';
    } else {
        $message = 'Paiement confirmé ! Votre abonnement est maintenant actif.';
        $messageType = 'success';
    }
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'cancelled') {
    $message = 'Paiement annulé. Votre abonnement n\'a pas été modifié.';
    $messageType = 'warning';
}

if ($userId === '' || $token === '') {
    $message = "Session invalide ou jeton manquant.";
    $messageType = "danger";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
        $planId = (string)($_POST['id_subscription_type'] ?? '');
        $period = in_array((string)($_POST['period'] ?? 'monthly'), ['monthly', 'yearly'], true) ? (string)$_POST['period'] : 'monthly';

        if ($planId === '') {
            $message = 'Sélectionnez une formule avant de payer.';
            $messageType = 'warning';
        } else {
            $checkoutResp = callAPI(
                'http://silverhappy_api:8080/api/subscriptions/checkout',
                'POST',
                ['id_user' => $userId, 'id_subscription_type' => $planId, 'period' => $period],
                $token
            );

            if (is_array($checkoutResp) && isset($checkoutResp['checkout_url']) && $checkoutResp['checkout_url'] !== '') {
                header('Location: ' . $checkoutResp['checkout_url']);
                exit;
            }

            if (is_array($checkoutResp) && isset($checkoutResp['error'])) {
                $message = 'Erreur Stripe : ' . $checkoutResp['error'];
                $messageType = 'danger';
            } else {
                $message = 'Impossible de créer la session de paiement. Veuillez réessayer.';
                $messageType = 'danger';
            }
        }
    }

    // Fetch subscription types
    $plansResponse = callAPI('http://silverhappy_api:8080/api/subscription-types?user_type=senior', 'GET', null, $token);
    
    // Debug logging
    error_log('Plans Response Type: ' . gettype($plansResponse));
    error_log('Plans Response: ' . json_encode($plansResponse));
    
    if (is_array($plansResponse)) {
        if (isset($plansResponse['error'])) {
            $message = 'Erreur API abonnements : ' . $plansResponse['error'];
            $messageType = 'danger';
        } elseif (count($plansResponse) === 0) {
            $message = 'Aucune formule senior disponible pour le moment.';
            $messageType = 'warning';
        } else {
            $plans = $plansResponse;
        }
    } else {
        $message = 'Réponse API invalide (non-array): ' . json_encode($plansResponse);
        $messageType = 'danger';
    }

    // Fetch user subscriptions
    $subsResponse = callAPI('http://silverhappy_api:8080/api/users/' . urlencode($userId) . '/subscriptions', 'GET', null, $token);
    error_log('DEBUG: User subscriptions response: ' . json_encode($subsResponse));
    if (is_array($subsResponse) && !isset($subsResponse['error'])) {
        foreach ($subsResponse as $sub) {
            error_log('DEBUG: Subscription item: ' . json_encode($sub));
            if (($sub['status'] ?? '') === 'Actif') {
                $activeSubscription = $sub;
                error_log('DEBUG: Active subscription set: ' . json_encode($activeSubscription));
                break;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_my_subscription') {
        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        $confirmText = strtoupper(trim((string)($_POST['confirm_delete_text'] ?? '')));
        $confirmChecked = (string)($_POST['confirm_delete_subscription'] ?? '') === '1';

        if (!hash_equals($subscriptionCsrf, $csrfToken)) {
            $message = "Vérification de sécurité invalide. Veuillez réessayer.";
            $messageType = "danger";
        } elseif ($confirmText !== 'SUPPRIMER' || !$confirmChecked) {
            $message = "Veuillez cocher la confirmation et saisir SUPPRIMER pour valider la suppression.";
            $messageType = "warning";
        } elseif (!$activeSubscription) {
            $message = "Aucun abonnement actif à supprimer.";
            $messageType = "warning";
        } else {
            $deleteResp = callAPI(
                'http://silverhappy_api:8080/api/users/' . urlencode($userId) . '/subscriptions/' . urlencode($activeSubscription['id_subscription']),
                'DELETE', null, $token
            );
            if (isset($deleteResp['error'])) {
                $message = 'Erreur lors de la suppression : ' . $deleteResp['error'];
                $messageType = 'danger';
            } else {
                $message = "Votre abonnement a été supprimé.";
                $messageType = "success";
                $activeSubscription = null;
            }
        }
    }
}

?>

<?php include './include/header.php'; ?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Mes abonnements</h1>
            <p class="senier-subtitle">Choisissez la formule qui vous convient.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Profil/Abonnements</div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Abonnement actif</h5>
            <?php if ($activeSubscription): ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold fs-5"><?= htmlspecialchars((string)$activeSubscription['name']) ?></div>
                        <?php if (!empty($activeSubscription['subscription_start'])): ?>
                            <div class="text-muted small">
                                Début : <?= htmlspecialchars(date('d/m/Y', strtotime((string)$activeSubscription['subscription_start']))) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($activeSubscription['subscription_end'])): ?>
                            <div class="text-muted small">
                                Fin : <?= htmlspecialchars(date('d/m/Y', strtotime((string)$activeSubscription['subscription_end']))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-success">Actif</span>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDeleteSubscriptionConfirm()">
                        <i class="bi bi-trash"></i> Supprimer mon abonnement
                    </button>
                </div>
            <?php else: ?>
                <p class="mb-0 text-muted">Aucun abonnement actif pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Prendre un abonnement</h5>
            <?php if (empty($plans)): ?>
                <p class="mb-0 text-muted">Aucune formule senior disponible actuellement.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Formule</th>
                                <th>Mensuel</th>
                                <th>Annuel</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)$plan['name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars(getPlanDescription((string)$plan['id_subscription_type'], (string)$plan['name'], $plan['description'] ?? null)) ?></div>
                                    </td>
                                    <td><?= number_format((float)($plan['monthly_price'] ?? 0), 2, ',', ' ') ?> EUR</td>
                                    <td><?= number_format((float)($plan['yearly_price'] ?? 0), 2, ',', ' ') ?> EUR</td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            data-plan-id="<?= htmlspecialchars((string)$plan['id_subscription_type']) ?>"
                                            data-plan-name="<?= htmlspecialchars((string)$plan['name']) ?>"
                                            data-plan-monthly="<?= htmlspecialchars((string)number_format((float)($plan['monthly_price'] ?? 0), 2, ',', ' ')) ?>"
                                            data-plan-yearly="<?= htmlspecialchars((string)number_format((float)($plan['yearly_price'] ?? 0), 2, ',', ' ')) ?>"
                                            data-plan-description="<?= htmlspecialchars(getPlanDescription((string)$plan['id_subscription_type'], (string)$plan['name'], $plan['description'] ?? null), ENT_QUOTES) ?>"
                                            onclick="openSubscribeConfirm(this)"
                                        ><i class="bi bi-credit-card"></i> Payer par Stripe</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="modal fade" id="modalConfirmSubscription" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="subscribeForm">
                <input type="hidden" name="action" value="subscribe">
                <input type="hidden" name="id_subscription_type" id="subscribePlanId" value="">
                <input type="hidden" name="period" id="subscribePeriod" value="monthly">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer et payer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fw-semibold fs-6 mb-1" id="confirmPlanName"></div>
                    <p class="small text-muted mb-3" id="confirmPlanDescription"></p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choisir la période</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="period_option" id="periodMonthly" value="monthly" checked onclick="updateSubscribePeriod('monthly')">
                                <label class="form-check-label" for="periodMonthly">
                                    Mensuel — <span id="confirmPlanMonthly"></span> €/mois
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="period_option" id="periodYearly" value="yearly" onclick="updateSubscribePeriod('yearly')">
                                <label class="form-check-label" for="periodYearly">
                                    Annuel — <span id="confirmPlanYearly"></span> €/an
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-lock-fill"></i> Vous allez être redirigé vers la page de paiement sécurisé Stripe.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnPayStripe">
                        <i class="bi bi-credit-card"></i> Payer maintenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteSubscription" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supprimer mon abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_my_subscription">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($subscriptionCsrf) ?>">

                    <p class="small text-muted mb-3">Cette action résilie votre abonnement actif. Tapez SUPPRIMER pour confirmer.</p>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="confirm_delete_subscription" id="confirmDeleteSubscriptionCheckbox">
                        <label class="form-check-label" for="confirmDeleteSubscriptionCheckbox">
                            Je confirme vouloir supprimer/résilier mon abonnement.
                        </label>
                    </div>

                    <label for="confirmDeleteText" class="form-label">Tapez <strong>SUPPRIMER</strong> pour valider</label>
                    <input type="text" class="form-control" name="confirm_delete_text" id="confirmDeleteText" placeholder="SUPPRIMER" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer l'abonnement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedPlanId = ''; 

function openSubscribeConfirm(btn) {
    selectedPlanId = btn.getAttribute('data-plan-id') || '';
    document.getElementById('subscribePlanId').value = selectedPlanId;
    document.getElementById('confirmPlanName').textContent        = btn.getAttribute('data-plan-name') || '';
    document.getElementById('confirmPlanDescription').textContent = btn.getAttribute('data-plan-description') || '';
    document.getElementById('confirmPlanMonthly').textContent     = btn.getAttribute('data-plan-monthly') || '0,00';
    document.getElementById('confirmPlanYearly').textContent      = btn.getAttribute('data-plan-yearly') || '0,00';
    document.getElementById('periodMonthly').checked = true;
    document.getElementById('subscribePeriod').value = 'monthly';
    new bootstrap.Modal(document.getElementById('modalConfirmSubscription')).show();
}

function updateSubscribePeriod(period) {
    document.getElementById('subscribePeriod').value = period;
}

function openDeleteSubscriptionConfirm() {
    document.getElementById('confirmDeleteSubscriptionCheckbox').checked = false;
    document.getElementById('confirmDeleteText').value = '';
    new bootstrap.Modal(document.getElementById('modalDeleteSubscription')).show();
}
</script>

<?php include './include/footer.php'; ?>
