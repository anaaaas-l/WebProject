<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = '';

$categoriesStmt = db()->query('SELECT id, name FROM categories ORDER BY name');
$categories = $categoriesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);

    if ($title === '') {
        $errors[] = 'Le titre est obligatoire.';
    }

    if ($categoryId <= 0) {
        $errors[] = 'La catégorie est obligatoire.';
    }

    if (empty($_FILES['resource_file']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Le fichier est obligatoire.';
    } else {
        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
        ];

        $fileType = mime_content_type($_FILES['resource_file']['tmp_name']);
        if (!in_array($fileType, $allowed, true)) {
            $errors[] = 'Type de fichier non autorisé (PDF, Word, JPG, PNG).';
        }
    }

    if (!$errors) {
        $originalName = $_FILES['resource_file']['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . strtolower($extension);
        $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($_FILES['resource_file']['tmp_name'], $destination)) {
            $errors[] = 'Échec de l\'enregistrement du fichier.';
        } else {
            $insert = db()->prepare('INSERT INTO resources (title, category_id, file_name, file_path, file_size, is_approved, created_at)
                                     VALUES (:title, :category_id, :file_name, :file_path, :file_size, 0, NOW())');
            $insert->execute([
                'title' => $title,
                'category_id' => $categoryId,
                'file_name' => $originalName,
                'file_path' => $safeName,
                'file_size' => (int) $_FILES['resource_file']['size'],
            ]);

            $success = 'Document déposé avec succès. Il sera visible après validation par un administrateur.';
        }
    }
}

$pageTitle = 'Déposer un document';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">Déposer une ressource</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Titre du document</label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Choisir...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id']; ?>"><?= e($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier (PDF, Word, JPG, PNG)</label>
                        <input class="form-control" type="file" name="resource_file" required>
                    </div>
                    <button class="btn btn-primary">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
