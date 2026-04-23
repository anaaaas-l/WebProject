<?php
require_once __DIR__ . '/config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Document introuvable.');
}

$stmt = db()->prepare('SELECT id, file_name, file_path FROM resources WHERE id = :id AND is_approved = 1');
$stmt->execute(['id' => $id]);
$resource = $stmt->fetch();

if (!$resource) {
    http_response_code(404);
    exit('Document introuvable.');
}

$fullPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $resource['file_path'];
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('Fichier non disponible.');
}

$update = db()->prepare('UPDATE resources SET download_count = download_count + 1 WHERE id = :id');
$update->execute(['id' => $id]);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($resource['file_name']) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
