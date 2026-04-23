<?php
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) : 'Plateforme de partage'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/share/index.php">ShareRessources</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="/share/index.php">Accueil</a>
            <a class="nav-link" href="/share/upload.php">Déposer</a>
            <a class="nav-link" href="/share/admin/login.php">Admin</a>
        </div>
    </div>
</nav>
<main class="container pb-5">
