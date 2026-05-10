<?php
include './include/header.php';
require_once __DIR__ . '/include/callapi.php';

$prestations = [];
$events = [];
$testimonials = [];


try {
    $serviceCategories = callAPI('hhttp://silverhappy_api:8080/api/service-categories');
    $serviceTypes = callAPI('hhttp://silverhappy_api:8080/api/service-types');

    if (is_array($serviceCategories) && !isset($serviceCategories['error']) && is_array($serviceTypes) && !isset($serviceTypes['error'])) {
        $categoriesById = [];
        foreach ($serviceCategories as $category) {
            if (!empty($category['id_service_category'])) {
                $categoriesById[(string)$category['id_service_category']] = $category;
            }
        }

        foreach ($serviceTypes as $serviceType) {
            if (count($prestations) >= 8) {
                break;
            }

            $categoryId = (string)($serviceType['id_service_category'] ?? '');
            $category = $categoriesById[$categoryId] ?? null;
            $description = trim((string)($serviceType['description'] ?? ''));
            if ($description === '') {
                $description = trim((string)($category['description'] ?? ''));
            }

            $prestations[] = [
                'title' => (string)($serviceType['name'] ?? ''),
                'description' => $description,
                'price' => (float)($serviceType['hourly_rate'] ?? 0),
                'category' => (string)($category['name'] ?? 'Service'),
            ];
        }
    }
} catch (Throwable $e) {
    $prestations = [];
}

try {
    $eventsResponse = callAPI('hhttp://silverhappy_api:8080/api/events');
    if (is_array($eventsResponse) && !isset($eventsResponse['error'])) {
        usort($eventsResponse, static function ($left, $right) {
            return strcmp((string)($left['start_date'] ?? ''), (string)($right['start_date'] ?? ''));
        });

        foreach ($eventsResponse as $event) {
            if (count($events) >= 3) {
                break;
            }

            $events[] = [
                'title' => (string)($event['title'] ?? ''),
                'description' => (string)($event['event_type'] ?? ''),
                'date' => $event['start_date'] ?? '',
                'location' => '',
            ];
        }
    }
} catch (Throwable $e) {
    $events = [];
}

try {
    $testimonials = [];
} catch (Throwable $e) {
        $testimonials = [];
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
            <h1 class="hero-title mb-3"><?= t('home_hero_title') ?></h1>
            <p class="hero-text mb-4"><?= t('home_hero_text') ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#prestations" class="btn btn-primary"><?= t('home_hero_cta_services') ?></a>
                <a href="#" class="btn btn-outline-secondary"><?= t('home_hero_cta_member') ?></a>
            </div>
        </div>
        <div class="col-md-5 text-center mt-4 mt-md-0">
            <div class="hero-illustration">
                <img src="./img/logo.png" alt="Illustration Silver Happy" class="img-fluid rounded-circle shadow-sm hero-image">
            </div>
        </div>
    </div>
</section>

<section class="mb-5" aria-label="<?= t('home_advantages_aria') ?>">
    <div class="row text-center g-3">
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-people"></i></div>
                <h3 class="h5 mb-2"><?= t('home_adv_1_title') ?></h3>
                <p class="mb-0"><?= t('home_adv_1_text') ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-heart-pulse"></i></div>
                <h3 class="h5 mb-2"><?= t('home_adv_2_title') ?></h3>
                <p class="mb-0"><?= t('home_adv_2_text') ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="advantage-card h-100 p-4">
                <div class="advantage-icon mb-3"><i class="bi bi-calendar-event"></i></div>
                <h3 class="h5 mb-2"><?= t('home_adv_3_title') ?></h3>
                <p class="mb-0"><?= t('home_adv_3_text') ?></p>
            </div>
        </div>
    </div>
</section>

<section class="callout-prestataire mb-5">
    <div class="row align-items-center">
        <div class="col-md-8 mb-3 mb-md-0">
            <h2 class="section-title text-white mb-2"><?= t('home_provider_title') ?></h2>
            <p class="mb-0 text-white-50"><?= t('home_provider_text') ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="#" class="btn btn-light"><?= t('home_provider_cta') ?></a>
        </div>
    </div>
</section>

<section id="temoignages" class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="section-title mb-0"><?= t('home_testimonials_title') ?></h2>
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
