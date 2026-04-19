<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once '../db.php';

$seniorCurrent = 'messagerie';
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$errors = [];
$success = '';
$conversationList = [];
$providerOptions = [];
$selectedPeerId = trim((string)($_GET['with'] ?? ''));
$currentMessages = [];
$newContent = trim((string)($_POST['content'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPeerId = trim((string)($_POST['receiver'] ?? ''));

    if (!$pdo instanceof PDO) {
        $errors[] = 'Base de donnees indisponible.';
    }
    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($selectedPeerId === '') {
        $errors[] = 'Veuillez selectionner un destinataire.';
    }
    if ($newContent === '') {
        $errors[] = 'Le message ne peut pas etre vide.';
    }

    if (empty($errors)) {
        try {
            $insertStmt = $pdo->prepare(
                'INSERT INTO messages (id_message, content, sent_at, receiver, sender)
                 VALUES (?, ?, NOW(), ?, ?)'
            );
            $insertStmt->execute([
                'msg_' . bin2hex(random_bytes(8)),
                mb_substr($newContent, 0, 5000),
                $selectedPeerId,
                $userId,
            ]);

            header('Location: messagerie.php?with=' . urlencode($selectedPeerId) . '&sent=1');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Impossible d envoyer le message pour le moment.';
        }
    }
}

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $success = 'Message envoye.';
}

if ($pdo instanceof PDO && $userId !== '') {
    try {
        $providersStmt = $pdo->query(
              "SELECT id_user, first_name, last_name
             FROM users
             WHERE role = 'prestataire'
               ORDER BY last_name ASC, first_name ASC"
        );
        $providerOptions = $providersStmt ? $providersStmt->fetchAll() : [];

        $convStmt = $pdo->prepare(
            "SELECT sender, receiver, content, sent_at
             FROM messages
             WHERE sender = ? OR receiver = ?
             ORDER BY sent_at DESC"
        );
        $convStmt->execute([$userId, $userId]);
        $conversationRows = $convStmt->fetchAll();

        $conversationMap = [];
        foreach ($conversationRows as $row) {
            $sender = (string)($row['sender'] ?? '');
            $receiver = (string)($row['receiver'] ?? '');
            $peerId = $sender === $userId ? $receiver : $sender;

            if ($peerId === '') {
                continue;
            }

            if (!isset($conversationMap[$peerId])) {
                $conversationMap[$peerId] = [
                    'peer_id' => $peerId,
                    'last_sent_at' => (string)($row['sent_at'] ?? ''),
                    'last_message' => (string)($row['content'] ?? ''),
                ];
            }
        }
        $conversationList = array_values($conversationMap);

        if ($selectedPeerId === '' && !empty($conversationList)) {
            $selectedPeerId = (string)$conversationList[0]['peer_id'];
        }

        if ($selectedPeerId !== '') {
            $threadStmt = $pdo->prepare(
                "SELECT m.content, m.sent_at, m.sender, m.receiver,
                        us.first_name AS sender_first_name, us.last_name AS sender_last_name,
                        ur.first_name AS receiver_first_name, ur.last_name AS receiver_last_name
                 FROM messages m
                 LEFT JOIN users us ON us.id_user = m.sender
                 LEFT JOIN users ur ON ur.id_user = m.receiver
                 WHERE (m.sender = ? AND m.receiver = ?)
                    OR (m.sender = ? AND m.receiver = ?)
                 ORDER BY m.sent_at ASC"
            );
            $threadStmt->execute([$userId, $selectedPeerId, $selectedPeerId, $userId]);
            $currentMessages = $threadStmt->fetchAll();
        }
    } catch (Throwable $e) {
        $errors[] = 'Impossible de charger la messagerie.';
    }
}

include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Messagerie</h1>
            <p class="senier-subtitle">Bienvenue dans votre messagerie.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Messagerie</div>
    </div>

    <div class="senier-message-layout">
        <div class="senier-conversations">
            <div class="senier-search">
                <input type="text" class="form-control form-control-sm" placeholder="Conversations" disabled>
            </div>
            <?php if ($success): ?>
                <div class="alert alert-success py-2 px-2 mt-2 mb-2" role="alert"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2 px-2 mt-2 mb-2" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($conversationList)): ?>
                <div class="senier-empty">Aucune conversation pour le moment.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($conversationList as $conversation): ?>
                        <?php
                        $peerId = (string)$conversation['peer_id'];
                        $isActive = ($selectedPeerId === $peerId);
                        $label = $nameMap[$peerId] ?? $peerId;
                        if (strpos($peerId, 'usr_') === 0) {
                            $label = strtoupper(str_replace('usr_', '', $peerId));
                        }
                        ?>
                        <a
                            class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>"
                            href="messagerie.php?with=<?= urlencode($peerId) ?>"
                        >
                            <div class="fw-semibold"><?= htmlspecialchars($label) ?></div>
                            <small class="d-block text-truncate"><?= htmlspecialchars((string)($conversation['last_message'] ?? '')) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($providerOptions)): ?>
                <form method="get" class="mt-3">
                    <label class="form-label small mb-1" for="with">Contacter un prestataire</label>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="with" name="with">
                            <option value="">Selectionner...</option>
                            <?php foreach ($providerOptions as $provider): ?>
                                <?php
                                $pid = (string)$provider['id_user'];
                                $pname = trim((string)(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')));
                                if ($pname === '') {
                                    $pname = $pid;
                                }
                                ?>
                                <option value="<?= htmlspecialchars($pid) ?>" <?= $selectedPeerId === $pid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-success btn-sm">Ouvrir</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="senier-chat">
            <?php if ($selectedPeerId === ''): ?>
                <div class="senier-chat-empty">
                    <div class="fs-2 mb-2"><i class="bi bi-chat-dots"></i></div>
                    <h4>Sélectionnez une conversation</h4>
                    <p class="mb-0">Ou commencez en contactant un prestataire.</p>
                </div>
            <?php else: ?>
                <div class="border rounded p-3 mb-3" style="height:320px; overflow:auto; background:#fff;">
                    <?php if (empty($currentMessages)): ?>
                        <p class="text-muted mb-0">Aucun message pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($currentMessages as $message): ?>
                            <?php
                            $isMine = ((string)$message['sender'] === $userId);
                            $senderName = trim((string)(($message['sender_first_name'] ?? '') . ' ' . ($message['sender_last_name'] ?? '')));
                            if ($senderName === '') {
                                $senderName = (string)$message['sender'];
                            }
                            ?>
                            <div class="mb-2 <?= $isMine ? 'text-end' : '' ?>">
                                <div class="small text-muted"><?= htmlspecialchars($senderName) ?> - <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$message['sent_at']))) ?></div>
                                <div class="d-inline-block px-2 py-1 rounded <?= $isMine ? 'bg-success-subtle' : 'bg-light' ?>" style="max-width:80%;">
                                    <?= nl2br(htmlspecialchars((string)$message['content'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="post" class="d-flex flex-column gap-2">
                    <input type="hidden" name="receiver" value="<?= htmlspecialchars($selectedPeerId) ?>">
                    <textarea class="form-control" name="content" rows="3" placeholder="Votre message..." required><?= htmlspecialchars($newContent) ?></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-sm">Envoyer</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
