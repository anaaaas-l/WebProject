<?php
require_once __DIR__ . '/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Nom d\'utilisateur et mot de passe obligatoires.';
    } else {
        $stmt = db()->prepare('SELECT id FROM admins WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $existing = $stmt->fetch();

        if ($existing) {
            $error = 'Cet utilisateur existe déjà.';
        } else {
            $insert = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
            $insert->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $message = 'Compte admin créé avec succès. Supprimez ensuite ce fichier setup_admin.php pour la sécurité.';
        }
    }
}

$pageTitle = 'Création admin initiale';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h3 mb-3">Créer le premier compte admin</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message); ?></div>
        <?php endif; ?>
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
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button class="btn btn-primary">Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
