<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';
$seniorCurrent = 'abonnements';

$userId = (string)($_SESSION['user']['id_user'] ?? '');
$message = '';
$messageType = '';
$plans = [];
$activeSubscription = null;
$hasSubscribedStatus = false;
$hasSubscribedAt = false;
$hasCancelledAt = false;
$hasPlanDescription = false;

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


function ensureSubscribedSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS subscribed (
            id_user VARCHAR(255) NOT NULL,
            id_subscription_type VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Actif',
            subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            cancelled_at DATETIME NULL,
            PRIMARY KEY (id_user, id_subscription_type)
        )"
    );

    $colsStmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscribed'");
    $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];

    if (!in_array('status', $cols, true)) {
        $pdo->exec("ALTER TABLE subscribed ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Actif'");
    }
    if (!in_array('subscribed_at', $cols, true)) {
        $pdo->exec("ALTER TABLE subscribed ADD COLUMN subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
    if (!in_array('cancelled_at', $cols, true)) {
        $pdo->exec("ALTER TABLE subscribed ADD COLUMN cancelled_at DATETIME NULL");
    }
}

if (!($pdo instanceof PDO) || $userId === '') {
    $message = "Session invalide ou base indisponible.";
    $messageType = "danger";
} else {
    try {
        ensureSubscribedSchema($pdo);

        $colsStmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscribed'");
        $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $hasSubscribedStatus = in_array('status', $cols, true);
        $hasSubscribedAt = in_array('subscribed_at', $cols, true);
        $hasCancelledAt = in_array('cancelled_at', $cols, true);
    } catch (Throwable $e) {
        $hasSubscribedStatus = false;
        $hasSubscribedAt = false;
        $hasCancelledAt = false;
    }

    try {
        $planColsStmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscription_types'");
        $planCols = $planColsStmt ? $planColsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $hasPlanDescription = in_array('description', $planCols, true);
    } catch (Throwable $e) {
        $hasPlanDescription = false;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
        $subscriptionId = trim((string)($_POST['id_subscription_type'] ?? ''));
        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        $confirmText = strtoupper(trim((string)($_POST['confirm_text'] ?? '')));
        $confirmChecked = (string)($_POST['confirm_subscription'] ?? '') === '1';

        if ($subscriptionId === '') {
            $message = "Veuillez sélectionner une formule.";
            $messageType = "warning";
        } elseif (!hash_equals($subscriptionCsrf, $csrfToken)) {
            $message = "Vérification de sécurité invalide. Veuillez réessayer.";
            $messageType = "danger";
        } elseif ($confirmText !== 'CONFIRMER' || !$confirmChecked) {
            $message = "Veuillez cocher la confirmation et saisir CONFIRMER avant de valider.";
            $messageType = "warning";
        } else {
            try {
                // Verify user really exists in DB.
                $userExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_user = ?");
                $userExistsStmt->execute([$userId]);
                $userExists = (int)$userExistsStmt->fetchColumn() > 0;
                if (!$userExists) {
                    $message = "Votre compte n'existe pas dans la base locale (id: " . $userId . "). Déconnectez-vous puis reconnectez-vous.";
                    $messageType = "danger";
                    throw new RuntimeException('USER_NOT_FOUND_IN_DB');
                }

                // Verify selected plan exists.
                $planExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_types WHERE id_subscription_type = ?");
                $planExistsStmt->execute([$subscriptionId]);
                $planExists = (int)$planExistsStmt->fetchColumn() > 0;
                if (!$planExists) {
                    $message = "La formule sélectionnée n'existe plus.";
                    $messageType = "warning";
                    throw new RuntimeException('PLAN_NOT_FOUND');
                }

                $pdo->beginTransaction();

                // Un seul abonnement actif à la fois pour un senior.
                if ($hasSubscribedStatus) {
                    if ($hasCancelledAt) {
                        $cancelStmt = $pdo->prepare("UPDATE subscribed SET status='Résilié', cancelled_at=NOW() WHERE id_user=? AND status='Actif'");
                    } else {
                        $cancelStmt = $pdo->prepare("UPDATE subscribed SET status='Résilié' WHERE id_user=? AND status='Actif'");
                    }
                    $cancelStmt->execute([$userId]);
                } else {
                    $pdo->prepare("DELETE FROM subscribed WHERE id_user=?")->execute([$userId]);
                }

                if ($hasSubscribedStatus && $hasSubscribedAt && $hasCancelledAt) {
                    $subStmt = $pdo->prepare(
                        "INSERT INTO subscribed (id_user, id_subscription_type, status, subscribed_at, cancelled_at)
                         VALUES (?, ?, 'Actif', NOW(), NULL)
                         ON DUPLICATE KEY UPDATE status='Actif', subscribed_at=NOW(), cancelled_at=NULL"
                    );
                    $subStmt->execute([$userId, $subscriptionId]);
                } elseif ($hasSubscribedStatus && $hasSubscribedAt) {
                    $subStmt = $pdo->prepare(
                        "INSERT INTO subscribed (id_user, id_subscription_type, status, subscribed_at)
                         VALUES (?, ?, 'Actif', NOW())
                         ON DUPLICATE KEY UPDATE status='Actif', subscribed_at=NOW()"
                    );
                    $subStmt->execute([$userId, $subscriptionId]);
                } elseif ($hasSubscribedStatus) {
                    $subStmt = $pdo->prepare(
                        "INSERT INTO subscribed (id_user, id_subscription_type, status)
                         VALUES (?, ?, 'Actif')
                         ON DUPLICATE KEY UPDATE status='Actif'"
                    );
                    $subStmt->execute([$userId, $subscriptionId]);
                } else {
                    $subStmt = $pdo->prepare(
                        "INSERT INTO subscribed (id_user, id_subscription_type)
                         VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE id_subscription_type=VALUES(id_subscription_type)"
                    );
                    $subStmt->execute([$userId, $subscriptionId]);
                }

                $pdo->commit();
                $message = "Votre abonnement a bien été activé.";
                $messageType = "success";
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($messageType === '') {
                    $message = "Impossible d'activer l'abonnement: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_my_subscription') {
        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        $confirmText = strtoupper(trim((string)($_POST['confirm_delete_text'] ?? '')));
        $confirmChecked = (string)($_POST['confirm_delete_subscription'] ?? '') === '1';

        if (!hash_equals($subscriptionCsrf, $csrfToken)) {
            $message = "Vérification de sécurité invalide. Veuillez réessayer.";
            $messageType = "danger";
        } elseif ($confirmText !== 'SUPPRIMER' || !$confirmChecked) {
            $message = "Veuillez cocher la confirmation et saisir SUPPRIMER pour valider la suppression.";
            $messageType = "warning";
        } else {
            try {
                if ($hasSubscribedStatus) {
                    $stmt = $pdo->prepare("DELETE FROM subscribed WHERE id_user=? AND status='Actif'");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM subscribed WHERE id_user=?");
                    $stmt->execute([$userId]);
                }
                $message = "Votre abonnement a été supprimé.";
                $messageType = "success";
            } catch (Throwable $e) {
                $message = "Impossible de supprimer l'abonnement: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }

    try {
        $planSql = $hasPlanDescription
            ? "SELECT id_subscription_type, name, user_type, monthly_price, yearly_price, description
               FROM subscription_types
               WHERE LOWER(user_type) = 'senior'
               ORDER BY monthly_price ASC, name ASC"
            : "SELECT id_subscription_type, name, user_type, monthly_price, yearly_price, NULL AS description
               FROM subscription_types
               WHERE LOWER(user_type) = 'senior'
               ORDER BY monthly_price ASC, name ASC";
        $plansStmt = $pdo->query($planSql);
        $plans = $plansStmt ? $plansStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        $plans = [];
    }

    try {
        if ($hasSubscribedStatus && $hasSubscribedAt) {
            $currentStmt = $pdo->prepare(
                "SELECT st.id_subscription_type, st.name, st.monthly_price, st.yearly_price, s.subscribed_at
                 FROM subscribed s
                 INNER JOIN subscription_types st ON st.id_subscription_type = s.id_subscription_type
                 WHERE s.id_user = ? AND s.status = 'Actif'
                 ORDER BY s.subscribed_at DESC
                 LIMIT 1"
            );
        } elseif ($hasSubscribedStatus) {
            $currentStmt = $pdo->prepare(
                "SELECT st.id_subscription_type, st.name, st.monthly_price, st.yearly_price, NULL AS subscribed_at
                 FROM subscribed s
                 INNER JOIN subscription_types st ON st.id_subscription_type = s.id_subscription_type
                 WHERE s.id_user = ? AND s.status = 'Actif'
                 LIMIT 1"
            );
        } else {
            $currentStmt = $pdo->prepare(
                "SELECT st.id_subscription_type, st.name, st.monthly_price, st.yearly_price, NULL AS subscribed_at
                 FROM subscribed s
                 INNER JOIN subscription_types st ON st.id_subscription_type = s.id_subscription_type
                 WHERE s.id_user = ?
                 LIMIT 1"
            );
        }
        $currentStmt->execute([$userId]);
        $activeSubscription = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $activeSubscription = null;
    }
}

include './include/header.php';
?>

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
                        <?php if (!empty($activeSubscription['subscribed_at'])): ?>
                            <div class="text-muted small">
                                Activé le <?= htmlspecialchars(date('d/m/Y', strtotime((string)$activeSubscription['subscribed_at']))) ?>
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
                                        >Choisir cette formule</button>
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
            <div class="modal-header">
                <h5 class="modal-title">Confirmer votre abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="confirmSubscriptionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="subscribe">
                    <input type="hidden" name="id_subscription_type" id="confirmPlanId" value="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($subscriptionCsrf) ?>">

                    <div class="mb-2 fw-semibold" id="confirmPlanName"></div>
                    <p class="small text-muted mb-2" id="confirmPlanDescription"></p>
                    <div class="small mb-3">
                        Mensuel: <span id="confirmPlanMonthly"></span> EUR<br>
                        Annuel: <span id="confirmPlanYearly"></span> EUR
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="confirm_subscription" id="confirmSubscriptionCheckbox">
                        <label class="form-check-label" for="confirmSubscriptionCheckbox">
                            Je confirme vouloir activer cette formule.
                        </label>
                    </div>

                    <label for="confirmText" class="form-label">Tapez <strong>CONFIRMER</strong> pour valider</label>
                    <input type="text" class="form-control" name="confirm_text" id="confirmText" placeholder="CONFIRMER" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Valider l'abonnement</button>
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
function openSubscribeConfirm(btn) {
    document.getElementById('confirmPlanId').value = btn.getAttribute('data-plan-id') || '';
    document.getElementById('confirmPlanName').textContent = btn.getAttribute('data-plan-name') || '';
    document.getElementById('confirmPlanDescription').textContent = btn.getAttribute('data-plan-description') || '';
    document.getElementById('confirmPlanMonthly').textContent = btn.getAttribute('data-plan-monthly') || '0,00';
    document.getElementById('confirmPlanYearly').textContent = btn.getAttribute('data-plan-yearly') || '0,00';
    document.getElementById('confirmSubscriptionCheckbox').checked = false;
    document.getElementById('confirmText').value = '';
    new bootstrap.Modal(document.getElementById('modalConfirmSubscription')).show();
}

function openDeleteSubscriptionConfirm() {
    document.getElementById('confirmDeleteSubscriptionCheckbox').checked = false;
    document.getElementById('confirmDeleteText').value = '';
    new bootstrap.Modal(document.getElementById('modalDeleteSubscription')).show();
}
</script>

<?php include './include/footer.php'; ?>
