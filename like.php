<?php
require_once __DIR__ . '/config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $update = db()->prepare('UPDATE resources SET like_count = like_count + 1 WHERE id = :id AND is_approved = 1');
    $update->execute(['id' => $id]);
}

header('Location: /share/index.php');
exit;
