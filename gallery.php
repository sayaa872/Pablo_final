<?php
session_start();
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Pagination
$photos_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $photos_per_page;

// Récupérer le nombre total de photos
$total_sql = "SELECT COUNT(*) as total FROM photos";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_photos = $total_row['total'];
$total_pages = ceil($total_photos / $photos_per_page);

// Récupérer les photos pour la page actuelle
$sql = "SELECT * FROM photos ORDER BY upload_date DESC LIMIT ?, ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $photos_per_page);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $photos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $photos[] = $row;
    }
}

// Récupérer tous les albums pour le menu déroulant d'ajout aux albums
if (isLoggedIn()) {
    $albums_sql = "SELECT * FROM albums ORDER BY name";
    $albums_result = mysqli_query($conn, $albums_sql);
    $albums = [];
    
    if ($albums_result && mysqli_num_rows($albums_result) > 0) {
        while ($row = mysqli_fetch_assoc($albums_result)) {
            $albums[] = $row;
        }
    }
}

// Traitement de l'ajout d'une photo à un album
if (isLoggedIn() && isset($_POST['add_to_album']) && isset($_POST['photo_id']) && isset($_POST['album_id'])) {
    $photo_id = $_POST['photo_id'];
    $album_id = $_POST['album_id'];
    
    // Vérifier si la photo est déjà dans l'album
    $check_sql = "SELECT * FROM album_photos WHERE album_id = ? AND photo_id = ?";
    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "ii", $album_id, $photo_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) == 0) {
            // La photo n'est pas encore dans l'album, l'ajouter
            $insert_sql = "INSERT INTO album_photos (album_id, photo_id) VALUES (?, ?)";
            if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($insert_stmt, "ii", $album_id, $photo_id);
                if (mysqli_stmt_execute($insert_stmt)) {
                    // Rediriger pour éviter la resoumission du formulaire
                    header("Location: gallery.php?page=$page&added=1");
                    exit;
                }
            }
        } else {
            // La photo est déjà dans l'album
            header("Location: gallery.php?page=$page&exists=1");
            exit;
        }
    }
}

// Traitement de la suppression d'une photo (réservé aux administrateurs)
if (isAdmin() && isset($_POST['delete_photo']) && isset($_POST['photo_id'])) {
    $photo_id = $_POST['photo_id'];
    
    // Récupérer le nom du fichier pour le supprimer du serveur
    $file_sql = "SELECT filename FROM photos WHERE id = ?";
    if ($file_stmt = mysqli_prepare($conn, $file_sql)) {
        mysqli_stmt_bind_param($file_stmt, "i", $photo_id);
        mysqli_stmt_execute($file_stmt);
        $file_result = mysqli_stmt_get_result($file_stmt);
        
        if ($file_row = mysqli_fetch_assoc($file_result)) {
            $filename = $file_row['filename'];
            
            // Supprimer les références dans album_photos
            $delete_refs_sql = "DELETE FROM album_photos WHERE photo_id = ?";
            if ($delete_refs_stmt = mysqli_prepare($conn, $delete_refs_sql)) {
                mysqli_stmt_bind_param($delete_refs_stmt, "i", $photo_id);
                mysqli_stmt_execute($delete_refs_stmt);
            }
            
            // Supprimer la photo de la base de données
            $delete_sql = "DELETE FROM photos WHERE id = ?";
            if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                mysqli_stmt_bind_param($delete_stmt, "i", $photo_id);
                if (mysqli_stmt_execute($delete_stmt)) {
                    // Supprimer le fichier physique
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                    
                    // Rediriger pour éviter la resoumission du formulaire
                    header("Location: gallery.php?page=$page&deleted=1");
                    exit;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie Photos</title>
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
            <h1>Galerie de photos</h1>
            
            <?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
                <div class="alert alert-success">La photo a été ajoutée à l'album avec succès.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['exists']) && $_GET['exists'] == 1): ?>
                <div class="alert alert-info">Cette photo est déjà dans l'album sélectionné.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div class="alert alert-success">La photo a été supprimée avec succès.</div>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <div style="margin-bottom: 1rem;">
                    <a href="upload.php" class="btn btn-primary">Ajouter des photos</a>
                </div>
            <?php endif; ?>
            
            <?php if (empty($photos)): ?>
                <p>Aucune photo n'a encore été ajoutée.</p>
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
                                        <!-- Bouton pour ajouter à un album -->
                                        <button class="btn btn-secondary album-add-btn" data-photo-id="<?php echo $photo['id']; ?>">Ajouter à un album</button>
                                        
                                        <?php if (isAdmin()): ?>
                                        <!-- Formulaire de suppression -->
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                            <button type="submit" name="delete_photo" class="btn btn-danger delete-btn">Supprimer</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="gallery.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isLoggedIn() && !empty($albums)): ?>
                <!-- Modal pour sélectionner un album -->
                <div id="album-select-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3>Ajouter à un album</h3>
                        <form method="post">
                            <input type="hidden" id="selected-photo-id" name="photo_id" value="">
                            <div class="form-group">
                                <label for="album-id" class="form-label">Sélectionner un album</label>
                                <select name="album_id" id="album-id" class="form-control" required>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?php echo $album['id']; ?>"><?php echo htmlspecialchars($album['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_to_album" class="btn btn-primary">Ajouter</button>
                        </form>
                    </div>
                </div>
                
                <style>
                    .modal {
                        display: none;
                        position: fixed;
                        z-index: 1000;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        overflow: auto;
                        background-color: rgba(0,0,0,0.4);
                    }
                    
                    .modal-content {
                        background-color: #fefefe;
                        margin: 15% auto;
                        padding: 20px;
                        border: 1px solid #888;
                        width: 80%;
                        max-width: 500px;
                        border-radius: var(--border-radius);
                    }
                    
                    .close {
                        color: #aaa;
                        float: right;
                        font-size: 28px;
                        font-weight: bold;
                        cursor: pointer;
                    }
                    
                    .close:hover,
                    .close:focus {
                        color: black;
                        text-decoration: none;
                    }
                    
                    .gallery-actions {
                        display: flex;
                        justify-content: space-between;
                        margin-top: 10px;
                    }
                    
                    .pagination {
                        display: flex;
                        justify-content: center;
                        margin-top: 20px;
                    }
                    
                    .pagination a {
                        color: var(--primary-color);
                        padding: 8px 16px;
                        text-decoration: none;
                        border: 1px solid #ddd;
                        margin: 0 4px;
                    }
                    
                    .pagination a.active {
                        background-color: var(--primary-color);
                        color: white;
                        border: 1px solid var(--primary-color);
                    }
                    
                    .pagination a:hover:not(.active) {
                        background-color: #ddd;
                    }
                </style>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const modal = document.getElementById('album-select-modal');
                        const closeBtn = modal.querySelector('.close');
                        const albumAddBtns = document.querySelectorAll('.album-add-btn');
                        const selectedPhotoIdInput = document.getElementById('selected-photo-id');
                        
                        // Ouvrir le modal et définir l'ID de la photo
                        albumAddBtns.forEach(btn => {
                            btn.addEventListener('click', function() {
                                const photoId = this.dataset.photoId;
                                selectedPhotoIdInput.value = photoId;
                                modal.style.display = 'block';
                            });
                        });
                        
                        // Fermer le modal
                        closeBtn.addEventListener('click', function() {
                            modal.style.display = 'none';
                        });
                        
                        // Fermer le modal en cliquant à l'extérieur
                        window.addEventListener('click', function(event) {
                            if (event.target == modal) {
                                modal.style.display = 'none';
                            }
                        });
                    });
                </script>
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