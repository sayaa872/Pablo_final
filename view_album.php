<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de l'album est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: albums.php");
    exit;
}

$album_id = $_GET['id'];

// Récupérer les informations de l'album
$sql = "SELECT * FROM albums WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $album_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $album = $row;
    } else {
        // Album non trouvé
        header("Location: albums.php");
        exit;
    }
} else {
    // Erreur de préparation de la requête
    header("Location: albums.php");
    exit;
}

// Traitement de la suppression d'une photo de l'album
if (isLoggedIn() && isset($_POST['remove_photo']) && isset($_POST['photo_id'])) {
    $photo_id = $_POST['photo_id'];
    
    $delete_sql = "DELETE FROM album_photos WHERE album_id = ? AND photo_id = ?";
    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($delete_stmt, "ii", $album_id, $photo_id);
        if (mysqli_stmt_execute($delete_stmt)) {
            // Rediriger pour éviter la resoumission du formulaire
            header("Location: view_album.php?id=$album_id&removed=1");
            exit;
        }
    }
}

// Récupérer les photos de l'album
$photos_sql = "SELECT photos.* FROM photos 
             JOIN album_photos ON photos.id = album_photos.photo_id 
             WHERE album_photos.album_id = ? 
             ORDER BY album_photos.position";
if ($photos_stmt = mysqli_prepare($conn, $photos_sql)) {
    mysqli_stmt_bind_param($photos_stmt, "i", $album_id);
    mysqli_stmt_execute($photos_stmt);
    $photos_result = mysqli_stmt_get_result($photos_stmt);
    
    $photos = [];
    while ($photo_row = mysqli_fetch_assoc($photos_result)) {
        $photos[] = $photo_row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album Photo</title>
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
            <div class="album-header">
                <h1><?php echo htmlspecialchars($album['name']); ?></h1>
                <a href="albums.php" class="btn btn-secondary">Retour aux albums</a>
            </div>
            
            <?php if (isset($_GET['removed']) && $_GET['removed'] == 1): ?>
                <div class="alert alert-success">La photo a été retirée de l'album avec succès.</div>
            <?php endif; ?>
            
            <?php if (!empty($album['description'])): ?>
                <div class="album-description">
                    <p><?php echo htmlspecialchars($album['description']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (empty($photos)): ?>
                <p>Cet album ne contient aucune photo.</p>
                <?php if (isLoggedIn()): ?>
                    <p>Vous pouvez ajouter des photos depuis la <a href="gallery.php">galerie</a>.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="gallery">
                    <?php foreach ($photos as $photo): ?>
                        <div class="gallery-item">
                            <img src="<?php echo htmlspecialchars($photo['filename']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="gallery-img">
                            <div class="gallery-caption">
                                <h4><?php echo htmlspecialchars($photo['title']); ?></h4>
                                <?php if (!empty($photo['description'])): ?>
                                    <p><?php echo htmlspecialchars($photo['description']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isLoggedIn()): ?>
                                    <div class="gallery-actions">
                                        <!-- Formulaire pour retirer la photo de l'album -->
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                            <button type="submit" name="remove_photo" class="btn btn-danger">Retirer de l'album</button>
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

    <script src="assets/js/main.js"></script>
</body>
</html>