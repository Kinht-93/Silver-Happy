<?php
$seniorCurrent = 'messagerie';
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
                <input type="text" class="form-control form-control-sm" placeholder="Rechercher...">
            </div>
            <div class="senier-empty">Aucune conversation pour le moment</div>
        </div>

        <div class="senier-chat">
            <div class="senier-chat-empty">
                <div class="fs-2 mb-2"><i class="bi bi-chat-dots"></i></div>
                <h4>Sélectionnez une conversation</h4>
                <p class="mb-0">Ou commencez et contactez un prestataire</p>
            </div>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
