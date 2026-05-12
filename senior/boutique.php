<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$seniorCurrent = 'boutique';
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$message = $_SESSION['shop_message'] ?? '';
$messageType = $_SESSION['shop_message_type'] ?? '';
unset($_SESSION['shop_message'], $_SESSION['shop_message_type']);

$products = [];
$categories = [];
$selectedCategory = $_GET['category'] ?? 'all';
$cart = $_SESSION['shopping_cart'] ?? [];

if (isset($_GET['payment']) && $_GET['payment'] === 'success' && !empty($_GET['session_id'])) {
    $confirmResp = callAPI('http://localhost:8080/api/orders/confirm?session_id=' . urlencode($_GET['session_id']), 'GET', null, $token);
    if (is_array($confirmResp) && isset($confirmResp['error'])) {
        $message = 'Erreur confirmation paiement : ' . $confirmResp['error'];
        $messageType = 'danger';
    } else {
        $message = 'Paiement confirmé ! Votre commande est enregistrée.';
        $messageType = 'success';
        unset($_SESSION['shopping_cart']);
        $cart = [];
    }
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'cancelled') {
    $message = 'Paiement annulé. Votre commande n\'a pas été finalisée.';
    $messageType = 'warning';
}

if ($token !== '') {
    $productsResponse = callAPI('http://localhost:8080/api/products', 'GET', null, $token);
    $categoriesResponse = callAPI('http://localhost:8080/api/product-categories', 'GET', null, $token);

    if (is_array($productsResponse) && !isset($productsResponse['error'])) {
        $products = $productsResponse;
    }
    
    if (is_array($categoriesResponse) && !isset($categoriesResponse['error'])) {
        $categories = $categoriesResponse;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    $productId = (string)($_POST['id_product'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($productId !== '' && $quantity > 0) {
        if (!isset($cart[$productId])) {
            $cart[$productId] = [
                'name' => $_POST['name'] ?? '',
                'price' => (float)($_POST['price'] ?? 0),
                'quantity' => $quantity
            ];
        } else {
            $cart[$productId]['quantity'] += $quantity;
        }
        $_SESSION['shopping_cart'] = $cart;
        $_SESSION['shop_message'] = 'Produit ajouté au panier.';
        $_SESSION['shop_message_type'] = 'success';
        header('Location: boutique.php' . ($selectedCategory !== 'all' ? '?category=' . urlencode($selectedCategory) : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_from_cart') {
    $productId = (string)($_POST['id_product'] ?? '');
    if (isset($cart[$productId])) {
        unset($cart[$productId]);
        $_SESSION['shopping_cart'] = $cart;
        $_SESSION['shop_message'] = 'Produit retiré du panier.';
        $_SESSION['shop_message_type'] = 'info';
        header('Location: boutique.php?view=cart');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    if (empty($cart)) {
        $message = 'Votre panier est vide.';
        $messageType = 'warning';
    } else {
        $items = [];
        foreach ($cart as $productId => $item) {
            $items[] = [
                'id_product' => $productId,
                'quantity' => $item['quantity']
            ];
        }

        $response = callAPI('http://localhost:8080/api/orders/checkout', 'POST', [
            'id_user' => $userId,
            'items' => $items
        ], $token);

        if (is_array($response) && isset($response['checkout_url']) && $response['checkout_url'] !== '') {
            header('Location: ' . $response['checkout_url']);
            exit;
        }

        if (is_array($response) && isset($response['error'])) {
            $message = $response['error'];
            $messageType = 'danger';
        } else {
            $message = 'Impossible de créer la session de paiement. Veuillez réessayer.';
            $messageType = 'danger';
        }
    }
}

$filteredProducts = $products;
if ($selectedCategory !== 'all') {
    $filteredProducts = array_filter($products, function($p) use ($selectedCategory) {
        return $p['category'] === $selectedCategory;
    });
}

$cartTotal = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Boutique</h1>
            <p class="senier-subtitle">Découvrez nos produits et services.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Boutique</div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Catégories</h5>
                    <div class="list-group list-group-flush">
                        <a href="boutique.php" class="list-group-item list-group-item-action <?= $selectedCategory === 'all' ? 'active' : '' ?>">
                            Tous les produits
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="boutique.php?category=<?= urlencode((string)$category['name']) ?>" 
                               class="list-group-item list-group-item-action <?= $selectedCategory === $category['name'] ? 'active' : '' ?>">
                                <?= htmlspecialchars((string)$category['name']) ?>
                                <span class="badge bg-light text-dark float-end"><?= (int)($category['articles'] ?? 0) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($filteredProducts)): ?>
                        <p class="text-muted text-center py-5">Aucun produit disponible dans cette catégorie.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($filteredProducts as $product): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 d-flex flex-column">
                                        <h6 class="mb-2"><?= htmlspecialchars((string)$product['name']) ?></h6>
                                        <p class="text-muted small mb-2">
                                            Catégorie: <?= htmlspecialchars((string)$product['category']) ?>
                                        </p>
                                        <p class="mb-3">
                                            <strong><?= number_format((float)$product['price'], 2) ?>€</strong>
                                        </p>
                                        <p class="small text-muted mb-3">
                                            Stock: <?= (int)$product['stock'] ?>
                                        </p>
                                        <?php if ((int)$product['stock'] > 0): ?>
                                            <form method="POST" class="mt-auto">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="id_product" value="<?= htmlspecialchars((string)$product['id_product']) ?>">
                                                <input type="hidden" name="name" value="<?= htmlspecialchars((string)$product['name']) ?>">
                                                <input type="hidden" name="price" value="<?= htmlspecialchars((string)$product['price']) ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= (int)$product['stock'] ?>" style="max-width: 60px;">
                                                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Ajouter</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <p class="text-danger small">Rupture de stock</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Panier</h5>
                    <?php if (empty($cart)): ?>
                        <p class="text-muted text-center py-3">Votre panier est vide.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($cart as $productId => $item): ?>
                                <div class="list-group-item px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <div class="small fw-semibold"><?= htmlspecialchars((string)$item['name']) ?></div>
                                            <small class="text-muted">
                                                <?= (int)$item['quantity'] ?> x <?= number_format((float)$item['price'], 2) ?>€
                                                = <strong><?= number_format((float)$item['price'] * $item['quantity'], 2) ?>€</strong>
                                            </small>
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="remove_from_cart">
                                            <input type="hidden" name="id_product" value="<?= htmlspecialchars((string)$productId) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="border-top my-3 py-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong><?= number_format($cartTotal, 2) ?>€</strong>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="btn btn-success btn-sm w-100">Commander</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
