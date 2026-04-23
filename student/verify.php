<?php
require_once __DIR__ . '/../config.php';

$token = trim($_GET['token'] ?? '');
$message = '';
$isSuccess = false;

if ($token === '') {
    $message = 'Lien de verification invalide.';
} else {
    $stmt = db()->prepare('SELECT id, verify_token_expires_at, email_verified_at FROM students WHERE verify_token = :token LIMIT 1');
    $stmt->execute(['token' => $token]);
    $student = $stmt->fetch();

    if (!$student) {
        $message = 'Lien de verification introuvable.';
    } elseif (!empty($student['email_verified_at'])) {
        $message = 'Email deja verifie. Vous pouvez vous connecter.';
        $isSuccess = true;
    } elseif (!empty($student['verify_token_expires_at']) && strtotime((string) $student['verify_token_expires_at']) < time()) {
        $message = 'Ce lien a expire. Veuillez creer un nouveau compte.';
    } else {
        $update = db()->prepare('UPDATE students SET email_verified_at = NOW(), verify_token = NULL, verify_token_expires_at = NULL WHERE id = :id');
        $update->execute(['id' => $student['id']]);
        $message = 'Verification reussie. Vous pouvez maintenant vous connecter.';
        $isSuccess = true;
    }
}

$pageTitle = 'Verification Email';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4 text-center">
                <h1 class="h4 mb-3">Verification de l'email</h1>
                <div class="alert <?= $isSuccess ? 'alert-success' : 'alert-danger'; ?> mb-3">
                    <?= e($message); ?>
                </div>
                <a class="btn btn-primary" href="<?= e(app_url('student/login.php')); ?>">Retour a la connexion</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
