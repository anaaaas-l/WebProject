<?php
require_once __DIR__ . '/config.php';

$search = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);

$categoriesStmt = db()->query('SELECT id, name FROM categories ORDER BY name');
$categories = $categoriesStmt->fetchAll();

$sql = 'SELECT r.id, r.title, r.file_name, r.file_path, r.file_size, r.download_count, r.like_count, r.created_at, c.name AS category_name
        FROM resources r
        JOIN categories c ON c.id = r.category_id
        WHERE r.is_approved = 1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (r.title LIKE :search OR c.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $sql .= ' AND r.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$sql .= ' ORDER BY r.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$pageTitle = 'Accueil - Ressources';
require __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <h1 class="h3">Plateforme de partage de ressources</h1>
        <p class="text-muted mb-0">Consultez, recherchez et téléchargez des supports pédagogiques.</p>
    </div>
</div>

<form method="get" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" placeholder="Nom du document ou mot-clé..." value="<?= e($search); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Catégorie</label>
            <select name="category_id" class="form-select">
                <option value="0">Toutes</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id']; ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : ''; ?>>
                        <?= e($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit">Filtrer</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <?php if (!$resources): ?>
            <p class="mb-0 text-muted">Aucun document trouvé.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Taille</th>
                        <th>Téléchargements</th>
                        <th>Likes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td><?= e($resource['title']); ?></td>
                            <td><?= e($resource['category_name']); ?></td>
                            <td><?= number_format(((int) $resource['file_size']) / 1024, 1); ?> Ko</td>
                            <td><?= (int) $resource['download_count']; ?></td>
                            <td><?= (int) $resource['like_count']; ?></td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-success" href="<?= e(app_url('download.php?id=' . (int) $resource['id'])); ?>">Télécharger</a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('like.php?id=' . (int) $resource['id'])); ?>">Like</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
