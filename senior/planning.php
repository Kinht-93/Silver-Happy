<?php
$seniorCurrent = 'planning';
include './include/header.php';
?>

<section class="senier-page">
    <div class="senier-head d-flex flex-wrap align-items-end gap-2">
        <div>
            <h1 class="senier-title">Planning</h1>
            <p class="senier-subtitle">Retrouvez vos événements et prestations planifiés.</p>
        </div>
        <div class="senier-breadcrumb">Accueil/Planning</div>
    </div>

    <div class="senier-layout">
        <aside>
            <div class="senier-legend mb-2">
                <h4>Légende :</h4>
                <div class="senier-legend-item"><span class="senier-dot" style="background:#4f46e5;"></span> Événements</div>
                <div class="senier-legend-item"><span class="senier-dot" style="background:#10b981;"></span> Prestations</div>
                <div class="senier-legend-item mb-0"><span class="senier-dot" style="background:#0ea5e9;"></span> RV médical</div>
            </div>
            <button type="button" class="btn btn-success btn-sm w-100 mb-2">S'inscrire à un événement</button>
            <button type="button" class="btn btn-outline-success btn-sm w-100">Demander une prestation</button>
        </aside>

        <div class="senier-panel">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h3 class="senier-panel-title mb-0">Mois</h3>
                <div class="senier-calendar-nav">
                    <button type="button" class="btn btn-outline-success btn-sm">&lt;</button>
                    <button type="button" class="btn btn-outline-success btn-sm">&gt;</button>
                </div>
            </div>

            <table class="senier-calendar">
                <thead>
                    <tr>
                        <th>Lun</th>
                        <th>Mar</th>
                        <th>Mer</th>
                        <th>Jeu</th>
                        <th>Ven</th>
                        <th>Sam</th>
                        <th>Dim</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include './include/footer.php'; ?>
