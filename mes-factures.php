<?php
include './include/header.php';
?>

<section class="account-section">
    <div class="account-card">
        <a href="index.php" class="account-back-link">
            <i class="bi bi-arrow-left"></i>
            Retour au tableau de bord
        </a>

        <div class="invoice-panel">
            <h1 class="account-title mb-3">Historique des factures</h1>
            <div class="invoice-empty-state">
                Aucune facture disponible pour le moment.
            </div>
        </div>
    </div>
</section>

<?php
include './include/footer.php';
?>
