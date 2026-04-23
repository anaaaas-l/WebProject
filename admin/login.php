<?php
require_once __DIR__ . '/../config.php';

if (isAdmin()) {
    header('Location: /share/admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: /share/admin/dashboard.php');
        exit;
    }

    $error = 'Identifiants invalides.';
}

$pageTitle = 'Connexion Admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h3 mb-3">Espace administrateur</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
