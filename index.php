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

// Récupérer les dernières photos
$sql = "SELECT * FROM photos ORDER BY upload_date DESC LIMIT 8";
$result = mysqli_query($conn, $sql);
$photos = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $photos[] = $row;
    }
}

// Récupérer les albums
$sql = "SELECT albums.*, COUNT(album_photos.photo_id) as photo_count 
       FROM albums 
       LEFT JOIN album_photos ON albums.id = album_photos.album_id 
       GROUP BY albums.id 
       ORDER BY albums.created_at DESC LIMIT 4";
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
    <title>Pablo ♡</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">Pablo ♡</a>
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
        <section class="hero">
            <div class="container">
                <h1>Bienvenue dans l'album photo de Pablo ♡</h1>
                <p class="paw-emoji">🐾🐾</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="login.php" class="btn btn-primary">Se connecter</a>
                <?php else: ?>
                    <a href="upload.php" class="btn btn-primary">Ajouter des photos</a>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <div class="container">
                <h2>Dernières photos</h2>
                <?php if (empty($photos)): ?>
                    <p>Aucune photo n'a encore été ajoutée.</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="upload.php" class="btn btn-secondary">Ajouter des photos</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="gallery">
                        <?php foreach ($photos as $photo): ?>
                            <div class="gallery-item">
                                <img src="<?php echo htmlspecialchars($photo['filename']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="gallery-img">
                                <div class="gallery-caption">
                                    <h4><?php echo htmlspecialchars($photo['title']); ?></h4>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="gallery.php" class="btn btn-secondary">Voir toutes les photos</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <div class="container">
                <h2>Albums</h2>
                <?php if (empty($albums)): ?>
                    <p>Aucun album n'a encore été créé.</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="create_album.php" class="btn btn-secondary">Créer un album</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="album-list">
                        <?php foreach ($albums as $album): ?>
                            <div class="album-card">
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
                                                echo "<img src='{$preview_photos[$i]}' alt='Preview' class='preview-img'>";
                                            } else {
                                                echo "<div class='preview-placeholder'></div>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="album-info">
                                    <h3 class="album-title"><?php echo htmlspecialchars($album['name']); ?></h3>
                                    <p class="album-count"><?php echo $album['photo_count']; ?> photo(s)</p>
                                    <a href="view_album.php?id=<?php echo $album['id']; ?>" class="btn btn-primary">Voir l'album</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="albums.php" class="btn btn-secondary">Voir tous les albums</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <ul class="footer-links">
                <li><a href="index.php" class="footer-link">Accueil</a></li>
                <li><a href="gallery.php" class="footer-link">Galerie</a></li>
                <li><a href="albums.php" class="footer-link">Albums</a></li>
            </ul>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>