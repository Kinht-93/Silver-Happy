<?php
$seniorCurrent = $seniorCurrent ?? '';

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => 'index.php'],
    ['key' => 'profil', 'label' => 'Mon profil', 'href' => 'mon-profil.php'],
    ['key' => 'prestations', 'label' => 'Prestations', 'href' => 'prestations.php'],
    ['key' => 'evenements', 'label' => 'Événements', 'href' => 'evenements.php'],
];
?>

<aside class="senior-menu">
    <div class="senior-topbar">
        <h2 class="senior-menu-title">ESPACE SENIOR</h2>
        <nav class="senior-topbar-nav" aria-label="Navigation espace senior">
            <?php foreach ($menuItems as $item): ?>
                <a
                    class="senior-menu-link <?php echo $seniorCurrent === $item['key'] ? 'is-active' : ''; ?>"
                    href="<?php echo $item['href']; ?>"
                >
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>
