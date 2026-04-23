# Plateforme de Partage de Ressources

Application web en `PHP + MySQL + Bootstrap` pour partager des ressources pédagogiques.

## Fonctionnalités implémentées

- Étudiants:
  - Consultation des documents validés
  - Recherche par mot-clé
  - Filtre par catégorie
  - Téléchargement des fichiers
  - Dépôt de documents (soumis à validation admin)
  - Like sur les documents
- Administrateur:
  - Connexion sécurisée
  - Validation / suppression des documents
  - Ajout et modification des catégories
  - Statistiques: nombre de fichiers + téléchargements

## Installation (XAMPP)

1. Placez le projet dans `htdocs/share`.
2. Démarrez `Apache` et `MySQL` dans XAMPP.
3. Ouvrez `http://localhost/phpmyadmin`.
4. Importez le fichier `database.sql`.
5. Ouvrez `http://localhost/share/setup_admin.php` pour créer le premier admin.
6. Connectez-vous via `http://localhost/share/admin/login.php`.

## Structure

- `index.php` : liste/recherche des ressources
- `upload.php` : dépôt d'un document
- `download.php` : téléchargement + incrément du compteur
- `like.php` : système de like
- `admin/dashboard.php` : modération, catégories, stats
- `database.sql` : schéma de base de données

## Important

Après création du premier compte admin, supprimez `setup_admin.php` pour la sécurité.
