<?php
$seniorCurrent = $seniorCurrent ?? '';

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => 'index.php', 'icon' => 'bi-grid-1x2-fill'],
    ['key' => 'planning', 'label' => 'Mon planning', 'href' => 'planning.php', 'icon' => 'bi-calendar3'],
    ['key' => 'messagerie', 'label' => 'Messagerie', 'href' => 'messagerie.php', 'icon' => 'bi-chat-dots-fill'],
    ['key' => 'prestation', 'label' => 'Prestations', 'href' => 'prestation.php', 'icon' => 'bi-briefcase'],
    ['key' => 'factures', 'label' => 'Mes factures', 'href' => 'mes-factures.php', 'icon' => 'bi-receipt'],
    ['key' => 'profil', 'label' => 'Mon profil', 'href' => 'mon-profil.php', 'icon' => 'bi-person-fill'],
    ['key' => 'contact', 'label' => 'Contact', 'href' => 'contact.php', 'icon' => 'bi-envelope'],
    ['key' => 'logout', 'label' => 'Déconnexion', 'href' => '../logout.php', 'icon' => 'bi-box-arrow-right', 'danger' => true],
];

$activeAliases = [
    'prestation' => ['prestation', 'prestations', 'evenements'],
    'dashboard' => ['dashboard'],
    'planning' => ['planning'],
    'messagerie' => ['messagerie'],
    'factures' => ['factures'],
    'profil' => ['profil'],
    'contact' => ['contact'],
    'logout' => ['logout'],
];
?>

<aside class="senier-home-nav">
    <?php foreach ($menuItems as $item): ?>
        <?php
            $aliases = $activeAliases[$item['key']] ?? [$item['key']];
            $isActive = in_array($seniorCurrent, $aliases, true);
            $isDanger = !empty($item['danger']);
        ?>
        <a class="senier-home-link <?php echo $isActive ? 'is-active' : ''; ?> <?php echo $isDanger ? 'is-danger' : ''; ?>" href="<?php echo $item['href']; ?>">
            <i class="bi <?php echo $item['icon']; ?>"></i>
            <?php echo htmlspecialchars($item['label']); ?>
        </a>
    <?php endforeach; ?>

    <div class="senier-help-box-mini mt-2">
        <div class="mb-1 fw-semibold">Besoin d'aide ?</div>
        <p>Nos conseillers sont là pour vous aider</p>
        <a href="tel:0123456789">01-23-45-67-89</a>
    </div>
</aside>
