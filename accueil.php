<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Accueil';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5 text-center">
                <h1 class="h3 mb-3">Bienvenue</h1>
                <p class="text-muted mb-4">Choisissez votre espace pour continuer.</p>

                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                    <a class="btn btn-primary btn-lg px-4" href="<?= e(app_url('student/login.php')); ?>">Je suis Etudiant</a>
                    <a class="btn btn-outline-dark btn-lg px-4" href="<?= e(app_url('admin/login.php')); ?>">Je suis Admin</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
