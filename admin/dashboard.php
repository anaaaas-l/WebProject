<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
/* hada rah cmnt assi anas b s 1 */

if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE resources SET is_approved = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    header('Location: /share/admin/dashboard.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $select = db()->prepare('SELECT file_path FROM resources WHERE id = :id');
        $select->execute(['id' => $id]);
        $resource = $select->fetch();
        if ($resource) {
            $file = UPLOAD_DIR . DIRECTORY_SEPARATOR . $resource['file_path'];
            if (is_file($file)) {
                unlink($file);
            }
            $delete = db()->prepare('DELETE FROM resources WHERE id = :id');
            $delete->execute(['id' => $id]);
        }
    }
    header('Location: /share/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category'])) {
    $name = trim($_POST['new_category']);
    if ($name !== '') {
        $insert = db()->prepare('INSERT INTO categories (name) VALUES (:name)');
        $insert->execute(['name' => $name]);
    }
    header('Location: /share/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'], $_POST['edit_category_name'])) {
    $id = (int) $_POST['edit_category_id'];
    $name = trim($_POST['edit_category_name']);
    if ($id > 0 && $name !== '') {
        $update = db()->prepare('UPDATE categories SET name = :name WHERE id = :id');
        $update->execute([
            'name' => $name,
            'id' => $id,
        ]);
    }
    header('Location: /share/admin/dashboard.php');
    exit;
}

$stats = [
    'total_files' => (int) db()->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
    'total_downloads' => (int) db()->query('SELECT COALESCE(SUM(download_count),0) FROM resources')->fetchColumn(),
];

$pending = db()->query('SELECT r.id, r.title, c.name AS category_name, r.created_at
                        FROM resources r
                        JOIN categories c ON c.id = r.category_id
                        WHERE r.is_approved = 0
                        ORDER BY r.created_at DESC')->fetchAll();

$allCategories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Dashboard Admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Dashboard Admin</h1>
    <a class="btn btn-outline-secondary btn-sm" href="/share/admin/logout.php">Déconnexion</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-muted">Nombre total de fichiers</h2>
                <p class="display-6 mb-0"><?= $stats['total_files']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-muted">Nombre total de téléchargements</h2>
                <p class="display-6 mb-0"><?= $stats['total_downloads']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Documents en attente de validation</div>
            <div class="card-body">
                <?php if (!$pending): ?>
                    <p class="text-muted mb-0">Aucun document en attente.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pending as $doc): ?>
                                <tr>
                                    <td><?= e($doc['title']); ?></td>
                                    <td><?= e($doc['category_name']); ?></td>
                                    <td><?= e($doc['created_at']); ?></td>
                                    <td class="d-flex gap-2">
                                        <a class="btn btn-sm btn-success" href="/share/admin/dashboard.php?approve=<?= (int) $doc['id']; ?>">Approuver</a>
                                        <a class="btn btn-sm btn-danger" href="/share/admin/dashboard.php?delete=<?= (int) $doc['id']; ?>" onclick="return confirm('Supprimer ce document ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Ajouter une catégorie</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <input class="form-control" name="new_category" placeholder="Ex: Physique">
                    </div>
                    <button class="btn btn-primary btn-sm">Ajouter</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Modifier une catégorie</div>
            <div class="card-body">
                <?php foreach ($allCategories as $category): ?>
                    <form method="post" class="d-flex gap-2 mb-2">
                        <input type="hidden" name="edit_category_id" value="<?= (int) $category['id']; ?>">
                        <input class="form-control form-control-sm" name="edit_category_name" value="<?= e($category['name']); ?>">
                        <button class="btn btn-outline-primary btn-sm">MAJ</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
