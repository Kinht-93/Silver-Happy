<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../include/callapi.php';

$adminCurrent = 'messagerie';
$token = (string)($_SESSION['user']['token'] ?? '');
$userId = (string)($_SESSION['user']['id_user'] ?? '');

$errors = [];
$success = '';
$conversationList = [];
$userOptions = [];
$selectedPeerId = trim((string)($_GET['with'] ?? ''));
$currentMessages = [];
$newContent = trim((string)($_POST['content'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPeerId = trim((string)($_POST['receiver'] ?? ''));

    if ($userId === '') {
        $errors[] = 'Session utilisateur invalide.';
    }
    if ($token === '') {
        $errors[] = 'Authentification API indisponible.';
    }
    if ($selectedPeerId === '') {
        $errors[] = 'Veuillez selectionner un destinataire.';
    }
    if ($newContent === '') {
        $errors[] = 'Le message ne peut pas etre vide.';
    }

    if (empty($errors)) {
        $response = callAPI('http://silverhappy_api:8080/api/messages', 'POST', [
            'content' => mb_substr($newContent, 0, 5000),
            'receiver' => $selectedPeerId,
            'sender' => $userId,
        ], $token);

        if (is_array($response) && !isset($response['error'])) {
            header('Location: messagerie.php?with=' . urlencode($selectedPeerId) . '&sent=1');
            exit;
        } else {
            $apiError = is_array($response) ? trim((string)($response['error'] ?? '')) : '';
            $errors[] = $apiError !== ''
                ? 'Impossible d envoyer le message : ' . $apiError
                : 'Impossible d envoyer le message pour le moment.';
        }
    }
}

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $success = 'Message envoye.';
}

if ($token !== '' && $userId !== '') {
    $usersResponse = callAPI('http://silverhappy_api:8080/api/users-summary', 'GET', null, $token);
    $messagesResponse = callAPI('http://silverhappy_api:8080/api/messages?id_user=' . urlencode($userId), 'GET', null, $token);

    if (
        !is_array($usersResponse) || isset($usersResponse['error']) ||
        !is_array($messagesResponse) || isset($messagesResponse['error'])
    ) {
        $errors[] = 'Impossible de charger la messagerie.';
    } else {
        $userMap = [];
        foreach ($usersResponse as $user) {
            $id = (string)($user['id_user'] ?? '');
            if ($id === '') {
                continue;
            }
            $fullName = trim((string)(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
            $userMap[$id] = [
                'name' => $fullName !== '' ? $fullName : $id,
                'role' => (string)($user['role'] ?? ''),
            ];
            if ($id !== $userId) {
                $userOptions[] = $user;
            }
        }

        $conversationMap = [];
        foreach ($messagesResponse as $row) {
            $sender = (string)($row['sender'] ?? '');
            $receiver = (string)($row['receiver'] ?? '');
            $peerId = $sender === $userId ? $receiver : $sender;

            if ($peerId === '' || !isset($userMap[$peerId])) {
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

        usort($conversationList, static function ($left, $right) {
            return strcmp((string)($right['last_sent_at'] ?? ''), (string)($left['last_sent_at'] ?? ''));
        });

        if ($selectedPeerId === '' && !empty($conversationList)) {
            $selectedPeerId = (string)$conversationList[0]['peer_id'];
        }

        if ($selectedPeerId !== '') {
            $thread = [];
            foreach ($messagesResponse as $row) {
                $sender = (string)($row['sender'] ?? '');
                $receiver = (string)($row['receiver'] ?? '');
                if (!(($sender === $userId && $receiver === $selectedPeerId) || ($sender === $selectedPeerId && $receiver === $userId))) {
                    continue;
                }
                $row['sender_first_name'] = $sender !== '' && isset($userMap[$sender]) ? $userMap[$sender]['name'] : $sender;
                $row['sender_last_name'] = '';
                $row['receiver_first_name'] = $receiver !== '' && isset($userMap[$receiver]) ? $userMap[$receiver]['name'] : $receiver;
                $row['receiver_last_name'] = '';
                $thread[] = $row;
            }

            usort($thread, static function ($left, $right) {
                return strcmp((string)($left['sent_at'] ?? ''), (string)($right['sent_at'] ?? ''));
            });

            $currentMessages = $thread;
        }
    }
}

include __DIR__ . '/include/header-admin.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Messagerie</h1>
            <p class="senier-subtitle">Gestion de la messagerie.</p>
        </div>
        <div class="senier-breadcrumb">Admin/Messagerie</div>
    </div>

    <div class="senier-message-layout senier-message-layout--wide">
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
                        $label = isset($userMap[$peerId]) ? $userMap[$peerId]['name'] . ' (' . $userMap[$peerId]['role'] . ')' : $peerId;
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

            <?php if (!empty($userOptions)): ?>
                <form method="get" class="mt-3">
                    <label class="form-label small mb-1" for="with">Contacter un utilisateur</label>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="with" name="with">
                            <option value="">Selectionner...</option>
                            <?php foreach ($userOptions as $user): ?>
                                <?php
                                $uid = (string)$user['id_user'];
                                $uname = trim((string)(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                                $urole = (string)($user['role'] ?? '');
                                if ($uname === '') {
                                    $uname = $uid;
                                }
                                ?>
                                <option value="<?= htmlspecialchars($uid) ?>" <?= $selectedPeerId === $uid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($uname) ?> (<?= htmlspecialchars($urole) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-success btn-sm">Ouvrir</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="senier-chat <?= $selectedPeerId !== '' ? 'senier-chat--thread' : '' ?>">
            <?php if ($selectedPeerId === ''): ?>
                <div class="senier-chat-empty">
                    <div class="fs-2 mb-2"><i class="bi bi-chat-dots"></i></div>
                    <h4>Sélectionnez une conversation</h4>
                    <p class="mb-0">Ou commencez en contactant un utilisateur.</p>
                </div>
            <?php else: ?>
                <div class="border rounded p-3 mb-3 w-100" style="height:560px; overflow:auto; background:#fff;">
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

                <form method="post" class="d-flex flex-column gap-2 w-100">
                    <input type="hidden" name="receiver" value="<?= htmlspecialchars($selectedPeerId) ?>">
                    <textarea class="form-control" name="content" rows="8" placeholder="Votre message..." required><?= htmlspecialchars($newContent) ?></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-sm">Envoyer</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/include/footer-admin.php'; ?>
