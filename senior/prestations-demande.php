<?php
$seniorCurrent = 'prestations';
include './include/header.php';
?>

<section class="senior-shell">
    <div class="senior-content">
        <h1 class="senior-title">Demander une prestation</h1>
        <form class="senior-form" action="#" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="service_type">Type de prestation</label>
                    <input class="form-control" id="service_type" name="service_type" type="text">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="desired_date">Date souhaitée</label>
                    <input class="form-control" id="desired_date" name="desired_date" type="date">
                </div>
                <div class="col-12">
                    <label class="form-label" for="request_message">Détails de la demande</label>
                    <textarea class="form-control" id="request_message" name="request_message" rows="4"></textarea>
                </div>
            </div>
            <div class="senior-actions">
                <button class="btn btn-success" type="submit">Envoyer la demande</button>
                <a class="btn btn-outline-secondary" href="prestations.php">Retour</a>
            </div>
        </form>
    </div>
</section>

<?php include './include/footer.php'; ?>
