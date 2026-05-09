<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_LANG_ALLOWED = ['fr', 'en'];

if (isset($_GET['setlang'])) {
    $requested = strtolower(trim((string)$_GET['setlang']));
    if (in_array($requested, $_LANG_ALLOWED, true)) {
        $_SESSION['lang'] = $requested;
        setcookie('site_lang', $requested, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/',
            'samesite' => 'Lax',
        ]);

        $userId = (string)($_SESSION['user']['id_user'] ?? '');
        $userRole = strtolower((string)($_SESSION['user']['role'] ?? ''));
        if ($userId !== '' && $userRole === 'senior') {
            try {
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    include_once __DIR__ . '/../db.php';
                }
                if (isset($pdo) && $pdo instanceof PDO) {
                    $pdo->prepare(
                    "INSERT INTO senior_settings (id_user, language, font_size, email_notifications, updated_at)
                     VALUES (?, ?, 'Normale', 1, NOW())
                     ON DUPLICATE KEY UPDATE language = VALUES(language), updated_at = NOW()"
                    )->execute([$userId, $requested]);
                }
            } catch (Throwable $e) {
            }
        }
    }

    $redirect = strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?');
    $params = $_GET;
    unset($params['setlang']);
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirect);
    exit;
}

$_LANG_CODE = 'fr';

if (!empty($_SESSION['lang'])) {
    $langSession = strtolower((string)$_SESSION['lang']);
    if (in_array($langSession, $_LANG_ALLOWED, true)) {
        $_LANG_CODE = $langSession;
    }
} elseif (!empty($_COOKIE['site_lang'])) {
    $langCookie = strtolower((string)$_COOKIE['site_lang']);
    if (in_array($langCookie, $_LANG_ALLOWED, true)) {
        $_LANG_CODE = $langCookie;
        $_SESSION['lang'] = $langCookie;
    }
}

$userId = (string)($_SESSION['user']['id_user'] ?? '');
$userRole = strtolower((string)($_SESSION['user']['role'] ?? ''));
if ($userId !== '' && $userRole === 'senior') {
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            include_once __DIR__ . '/../db.php';
        }
        if (isset($pdo) && $pdo instanceof PDO) {
            $langRow = $pdo->prepare("SELECT language FROM senior_settings WHERE id_user = ?");
            $langRow->execute([$userId]);
            $langVal = strtolower((string)$langRow->fetchColumn());
            if (in_array($langVal, $_LANG_ALLOWED, true)) {
                $_LANG_CODE = $langVal;
                $_SESSION['lang'] = $langVal;
                setcookie('site_lang', $langVal, [
                    'expires' => time() + (365 * 24 * 60 * 60),
                    'path' => '/',
                    'samesite' => 'Lax',
                ]);
            }
        }
    } catch (Throwable $e) {
    }
}

if (!in_array($_LANG_CODE, $_LANG_ALLOWED, true)) {
    $_LANG_CODE = 'fr';
}

$_LANG_FILE = __DIR__ . '/lang/' . $_LANG_CODE . '.php';
if (!file_exists($_LANG_FILE)) {
    $_LANG_FILE = __DIR__ . '/lang/fr.php';
    $_LANG_CODE = 'fr';
}

$_TRANSLATIONS = require $_LANG_FILE;

if (!function_exists('t')) {
    function t(string $key, string $default = ''): string {
        global $_TRANSLATIONS;
        return htmlspecialchars(
            (string)($_TRANSLATIONS[$key] ?? ($default !== '' ? $default : $key)),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$GLOBALS['_LANG_CODE'] = $_LANG_CODE;
