<?php
include './include/header.php';
include_once 'db.php';

$prestations = [];
$events = [];
$testimonials = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT title, description, price, category FROM prestations WHERE validated = 1 ORDER BY created_at DESC LIMIT 8");
        $prestations = $stmt->fetchAll();
    } catch (Exception $e) {
        $prestations = [];
    }

    try {
        $stmt = $pdo->query("SELECT title, description, date, location FROM events ORDER BY date ASC LIMIT 3");
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        $events = [];
    }

    try {
        $sql = "SELECT t.content, t.rating, u.name FROM testimonials t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 3";
        $stmt = $pdo->query($sql);
        $testimonials = $stmt->fetchAll();
    } catch (Exception $e) {
        $testimonials = [];
    }
}

if (empty($prestations)) {
    $prestations = [
        ['title' => 'Aide à domicile', 'description' => 'Accompagnement au quotidien pour les tâches de la vie courante.', 'price' => 29.90, 'category' => 'Service'],
        ['title' => 'Atelier mémoire', 'description' => 'Exercices ludiques pour entretenir sa mémoire.', 'price' => 12.00, 'category' => 'Loisir'],
        ['title' => 'Coaching bien-être', 'description' => 'Conseils personnalisés pour rester en forme après 60 ans.', 'price' => 39.00, 'category' => 'Conseil'],
        ['title' => 'Boutique bien-être', 'description' => 'Sélection de produits adaptés aux besoins des seniors.', 'price' => 0, 'category' => 'Produit'],
        ['title' => 'Sortie culturelle', 'description' => 'Visites guidées et sorties culturelles organisées.', 'price' => 18.50, 'category' => 'Loisir'],
        ['title' => 'Cours de numérique', 'description' => 'Apprendre à utiliser smartphone, tablette et ordinateur.', 'price' => 15.00, 'category' => 'Service'],
        ['title' => 'Programme sport doux', 'description' => 'Gym douce et activité physique adaptée.', 'price' => 22.00, 'category' => 'Service'],
        ['title' => 'Atelier cuisine santé', 'description' => 'Ateliers pour cuisiner équilibré et gourmand.', 'price' => 25.00, 'category' => 'Loisir'],
    ];
}

if (empty($events)) {
    $events = [
        ['title' => 'Journée portes ouvertes', 'description' => 'Venez découvrir nos locaux, l’équipe et nos services.', 'date' => '25/03/2026', 'location' => 'Paris'],
        ['title' => 'Conférence bien vieillir', 'description' => 'Conseils de professionnels de santé et d’experts.', 'date' => '10/04/2026', 'location' => 'Lyon'],
        ['title' => 'Sortie au musée', 'description' => 'Visite guidée et moment convivial autour d’un café.', 'date' => '04/05/2026', 'location' => 'Bordeaux'],
    ];
}

if (empty($testimonials)) {
    $testimonials = [
        ['content' => 'Grâce à Silver Happy, je sors beaucoup plus et je me sens entourée.', 'rating' => 5, 'name' => 'Marie, 72 ans'],
        ['content' => 'Les ateliers numériques m’ont vraiment aidé à rester connecté avec ma famille.', 'rating' => 4, 'name' => 'Jean, 68 ans'],
        ['content' => 'Une équipe à l’écoute et des prestations de qualité.', 'rating' => 5, 'name' => 'Fatima, 75 ans'],
    ];
}
?>

<section id="presentation" class="hero-section mb-5">
    <div class="row align-items-center">
        <div class="col-md-7">
            <h1 class="hero-title mb-3">Vivez pleinement votre retraite avec Silver Happy</h1>
            <p class="hero-text mb-4">Services, loisirs, conseils et produits pensés pour les seniors de plus de 60 ans, afin de rester actifs, entourés et sereins au quotidien.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#prestations" class="btn btn-primary">Découvrir nos prestations</a>
                <a href="#" class="btn btn-outline-secondary">Devenir adhérent</a>
            </div>
        </div>
        <div class="col-md-5 text-center mt-4 mt-md-0">
            <div class="hero-illustration">
                <img src="./img/zelda-legend-of-zelda.gif" alt="Illustration Silver Happy" class="img-fluid rounded-circle shadow-sm hero-image">
            </div>
        </div>
    </div>
</section>

<section class="mb-5" aria-label="Les points forts de Silver Happy">
    <div class="row text-center g-3">
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-people"></i></div>
                <h3 class="h5 mb-2">Accompagnement humain</h3>
                <p class="mb-0">Une équipe à l’écoute pour vous proposer des solutions adaptées à votre rythme de vie.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-heart-pulse"></i></div>
                <h3 class="h5 mb-2">Bien-être & santé</h3>
                <p class="mb-0">Des activités et conseils pour rester en forme, physiquement et moralement.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-calendar-event"></i></div>
                <h3 class="h5 mb-2">Loisirs sur-mesure</h3>
                <p class="mb-0">Des sorties, ateliers et événements pour créer du lien et partager de bons moments.</p>
            </div>
        </div>
    </div>
</section>

<section id="prestations" class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="section-title mb-0">Nos prestations</h2>
        <a href="#" class="btn btn-link p-0">Voir toutes les prestations</a>
    </div>
    <div class="row g-3">
        <?php foreach ($prestations as $prestation): ?>
            <div class="col-6 col-md-3">
                <div class="card h-100 prestation-card">
                    <div class="card-body">
                        <h3 class="h6 card-title mb-1"><?php echo htmlspecialchars($prestation['title']); ?></h3>
                        <?php if (!empty($prestation['category'])): ?>
                            <span class="badge bg-primary-subtle text-primary small"><?php echo htmlspecialchars($prestation['category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($prestation['description'])):
                            $desc = $prestation['description'];
                            if (strlen($desc) > 90) {
                                $desc = substr($desc, 0, 87) . '...';
                            }
                        ?>
                            <p class="card-text mt-2 small"><?php echo htmlspecialchars($desc); ?></p>
                        <?php endif; ?>
                        <?php if (isset($prestation['price']) && $prestation['price'] > 0): ?>
                            <div class="mt-2 fw-semibold text-primary">
                                <?php echo number_format((float)$prestation['price'], 2, ',', ' '); ?> €
                            </div>
                        <?php else: ?>
                            <div class="mt-2 text-muted small">Sur devis</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="evenements" class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="section-title mb-0">Prochains événements</h2>
        <a href="#" class="btn btn-link p-0">Voir tout le calendrier</a>
    </div>
    <div class="row g-3">
        <?php foreach ($events as $event): ?>
            <div class="col-md-4">
                <div class="card h-100 event-card">
                    <div class="card-body">
                        <h3 class="h6 card-title mb-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar me-1"></i><?php echo htmlspecialchars($event['date']); ?>
                            <?php if (!empty($event['location'])): ?>
                                &nbsp;&middot; <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($event['location']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <p class="card-text small mb-0"><?php echo htmlspecialchars($event['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="callout-prestataire mb-5">
    <div class="row align-items-center">
        <div class="col-md-8 mb-3 mb-md-0">
            <h2 class="section-title text-white mb-2">Devenir prestataire Silver Happy</h2>
            <p class="mb-0 text-white-50">Vous proposez des services adaptés aux seniors ? Rejoignez notre réseau de partenaires de confiance et développez votre activité en toute sérénité.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="#" class="btn btn-light">Je deviens prestataire</a>
        </div>
    </div>
</section>

<section id="temoignages" class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="section-title mb-0">Ils parlent de nous</h2>
    </div>
    <div class="row g-3">
        <?php foreach ($testimonials as $testimonial): ?>
            <div class="col-md-4">
                <div class="card h-100 testimonial-card">
                    <div class="card-body">
                        <div class="testimonial-rating mb-2">
                            <?php
                            $rating = (int)($testimonial['rating'] ?? 0);
                            for ($i = 1; $i <= 5; $i++):
                                $full = $i <= $rating;
                            ?>
                                <i class="bi <?php echo $full ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-3 small"><?php echo htmlspecialchars($testimonial['content']); ?></p>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle"><?php echo strtoupper(substr($testimonial['name'], 0, 1)); ?></div>
                            <span class="fw-semibold small"><?php echo htmlspecialchars($testimonial['name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php
include './include/footer.php';
?>
