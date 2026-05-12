<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'commandes';
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$orders = [];
$message = $_SESSION['commandes_message'] ?? '';
$messageType = $_SESSION['commandes_message_type'] ?? '';
unset($_SESSION['commandes_message'], $_SESSION['commandes_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'refund_order') {
    $refundOrderId = (string)($_POST['order_id'] ?? '');
    if ($refundOrderId !== '' && $token !== '' && $userId !== '') {
        $refundResponse = callAPI('http://silverhappy_api:8080/api/orders/' . urlencode($refundOrderId) . '/refund', 'POST', null, $token);
        if (is_array($refundResponse) && !isset($refundResponse['error'])) {
            $_SESSION['commandes_message'] = 'Commande annulée et remboursée avec succès.';
            $_SESSION['commandes_message_type'] = 'success';
        } else {
            $_SESSION['commandes_message'] = 'Erreur : ' . ($refundResponse['error'] ?? 'Impossible de rembourser la commande.');
            $_SESSION['commandes_message_type'] = 'danger';
        }
    } else {
        $_SESSION['commandes_message'] = 'Commande invalide.';
        $_SESSION['commandes_message_type'] = 'danger';
    }
    header('Location: commandes.php');
    exit;
}

if ($token !== '' && $userId !== '') {
    $response = callAPI('http://silverhappy_api:8080/api/users/' . urlencode($userId) . '/orders', 'GET', null, $token);
    
    if (is_array($response) && !isset($response['error'])) {
        $orders = $response;
    } elseif (is_array($response) && isset($response['error'])) {
        $message = 'Erreur: ' . $response['error'];
        $messageType = 'danger';
    }
} else {
    $message = 'Session invalide ou token manquant.';
    $messageType = 'danger';
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'en attente':
            return 'bg-warning text-dark';
        case 'confirmée':
        case 'confirmee':
            return 'bg-info';
        case 'livrée':
        case 'livree':
            return 'bg-success';
        case 'annulée':
        case 'annulee':
            return 'bg-danger';
        case 'retour demandé':
        case 'retour demande':
            return 'bg-secondary';
        default:
            return 'bg-light text-dark';
    }
}

include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Mes commandes</h1>
        <p class="senior-subtitle">Consultez l'historique de vos commandes et leurs statuts.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <p class="text-muted text-center py-5">Vous n'avez pas encore de commandes. <a href="boutique.php">Découvrez la boutique</a></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro de commande</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Produits</th>
                                    <th>Livraison</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars((string)($order['order_number'] ?? 'N/A')) ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(date('d/m/Y à H:i', strtotime((string)($order['order_date'] ?? 'now')))) ?>
                                        </td>
                                        <td>
                                            <strong><?= number_format((float)($order['amount'] ?? 0), 2, ',', ' ') ?> €</strong>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars((string)($order['items'] ?? 'N/A')) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $deliveryMethod = strtolower((string)($order['delivery_method'] ?? ''));
                                                if ($deliveryMethod === 'delivery') {
                                                echo '<span class="badge bg-light text-dark">Livraison à domicile</span>';
                                            } else {
                                                echo '<span class="badge bg-light text-dark">' . htmlspecialchars((string)$order['delivery_method']) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = strtolower((string)($order['status'] ?? ''));
                                            if ($status === 'en attente') {
                                                echo '<span class="badge bg-warning text-dark">En attente</span>';
                                            } elseif ($status === 'confirmée') {
                                                echo '<span class="badge bg-info text-dark">Confirmée</span>';
                                            } elseif ($status === 'expédiée') {
                                                echo '<span class="badge bg-primary">Expédiée</span>';
                                            } elseif ($status === 'livrée') {
                                                echo '<span class="badge bg-success">Livrée</span>';
                                            } elseif ($status === 'annulée' || $status === 'remboursée') {
                                                echo '<span class="badge bg-danger">' . htmlspecialchars(ucfirst($status)) . '</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($status)) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (strtolower((string)($order['status'] ?? '')) === 'en attente'): ?>
                                                <form method="POST" onsubmit="return confirm('Voulez-vous vraiment annuler et rembourser cette commande ?');">
                                                    <input type="hidden" name="action" value="refund_order">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars((string)($order['id_order'] ?? '')) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Annuler</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="boutique.php" class="btn btn-primary">Continuer vos achats</a>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
