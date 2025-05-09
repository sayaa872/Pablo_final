<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Traitement de la création d'un nouvel album (si l'utilisateur est connecté)
if (isLoggedIn() && isset($_POST['create_album'])) {
    $album_name = sanitizeInput($_POST['album_name']);
    $album_description = sanitizeInput($_POST['album_description']);
    
    if (!empty($album_name)) {
        $sql = "INSERT INTO albums (name, description) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $album_name, $album_description);
            
            if (mysqli_stmt_execute($stmt)) {
                // Rediriger pour éviter la resoumission du formulaire
                header("Location: albums.php?created=1");
                exit;
            }
        }
    }
}

// Traitement de la suppression d'un album
if (isLoggedIn() && isset($_POST['delete_album']) && isset($_POST['album_id'])) {
    $album_id = $_POST['album_id'];
    
    $delete_sql = "DELETE FROM albums WHERE id = ?";
    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($delete_stmt, "i", $album_id);
        if (mysqli_stmt_execute($delete_stmt)) {
            // Rediriger pour éviter la resoumission du formulaire
            header("Location: albums.php?deleted=1");
            exit;
        }
    }
}

// Récupérer tous les albums avec le nombre de photos
$sql = "SELECT albums.*, COUNT(album_photos.photo_id) as photo_count 
       FROM albums 
       LEFT JOIN album_photos ON albums.id = album_photos.album_id 
       GROUP BY albums.id 
       ORDER BY albums.created_at DESC";
$result = mysqli_query($conn, $sql);
$albums = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $albums[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Albums</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">Pablo</a>
            <ul class="navbar-nav">
                <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
                <li class="nav-item"><a href="gallery.php" class="nav-link">Galerie</a></li>
                <li class="nav-item"><a href="albums.php" class="nav-link">Albums</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a href="upload.php" class="nav-link">Ajouter</a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1>Albums de photos</h1>
            
            <?php if (isset($_GET['created']) && $_GET['created'] == 1): ?>
                <div class="alert alert-success">L'album a été créé avec succès.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div class="alert alert-success">L'album a été supprimé avec succès.</div>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <div style="margin-bottom: 2rem;">
                    <button id="create-album-btn" class="btn btn-primary">Créer un nouvel album</button>
                </div>
                
                <!-- Formulaire de création d'album (caché par défaut) -->
                <div id="album-form-container" class="form-container" style="display: none; margin-bottom: 2rem;">
                    <h2>Créer un nouvel album</h2>
                    <form id="album-form" method="post">
                        <div class="form-group">
                            <label for="album-name" class="form-label">Nom de l'album</label>
                            <input type="text" name="album_name" id="album-name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="album-description" class="form-label">Description (optionnelle)</label>
                            <textarea name="album_description" id="album-description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="create_album" class="btn btn-primary">Créer</button>
                        <button type="button" id="cancel-album-btn" class="btn btn-secondary">Annuler</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if (empty($albums)): ?>
                <p>Aucun album n'a encore été créé.</p>
            <?php else: ?>
                <div class="album-list">
                    <?php foreach ($albums as $album): ?>
                        <div class="album-card">
                            <a href="view_album.php?id=<?php echo $album['id']; ?>" class="album-link">
                                <div class="album-preview">
                                    <div class="album-preview-grid">
                                        <?php 
                                        // Récupérer les 4 premières photos de l'album
                                        $sql = "SELECT photos.filename FROM photos 
                                               JOIN album_photos ON photos.id = album_photos.photo_id 
                                               WHERE album_photos.album_id = {$album['id']} 
                                               ORDER BY album_photos.position LIMIT 4";
                                        $result = mysqli_query($conn, $sql);
                                        $preview_photos = [];
                                        
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $preview_photos[] = $row['filename'];
                                            }
                                        }
                                        
                                        // Afficher les photos de prévisualisation
                                        for ($i = 0; $i < 4; $i++) {
                                            if (isset($preview_photos[$i])) {
                                                echo "<img src=\"" . htmlspecialchars($preview_photos[$i]) . "\" alt=\"Photo de prévisualisation\" class=\"preview-img\">";
                                            } else {
                                                echo "<div class=\"preview-placeholder\"></div>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>
                            <div class="album-info">
                                <h3 class="album-title"><?php echo htmlspecialchars($album['name']); ?></h3>
                                <p class="album-count"><?php echo $album['photo_count']; ?> photo<?php echo $album['photo_count'] > 1 ? 's' : ''; ?></p>
                                
                                <?php if (isLoggedIn()): ?>
                                    <div class="album-actions" style="margin-top: 10px;">
                                        <a href="view_album.php?id=<?php echo $album['id']; ?>" class="btn btn-secondary">Voir</a>
                                        
                                        <!-- Formulaire de suppression -->
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                                            <button type="submit" name="delete_album" class="btn btn-danger delete-btn">Supprimer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="footer-text">En mémoire de Pablo, notre fidèle ami à quatre pattes.</p>
            <ul class="footer-links">
                <li><a href="index.php" class="footer-link">Accueil</a></li>
                <li><a href="gallery.php" class="footer-link">Galerie</a></li>
                <li><a href="albums.php" class="footer-link">Albums</a></li>
            </ul>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion du formulaire de création d'album
            const createAlbumBtn = document.getElementById('create-album-btn');
            const albumFormContainer = document.getElementById('album-form-container');
            const cancelAlbumBtn = document.getElementById('cancel-album-btn');
            
            if (createAlbumBtn && albumFormContainer) {
                createAlbumBtn.addEventListener('click', function() {
                    albumFormContainer.style.display = 'block';
                    createAlbumBtn.style.display = 'none';
                });
                
                cancelAlbumBtn.addEventListener('click', function() {
                    albumFormContainer.style.display = 'none';
                    createAlbumBtn.style.display = 'inline-block';
                });
            }
        });
    </script>
    
    <script src="assets/js/main.js"></script>
</body>
</html>