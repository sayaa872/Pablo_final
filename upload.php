<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Traitement du formulaire d'upload
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload'])) {
    // Vérifier si un fichier a été uploadé
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        // Récupérer les données du formulaire
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        
        // Upload de la photo
        $uploaded_file = uploadPhoto($_FILES["photo"]);
        
        if ($uploaded_file) {
            // Insérer les informations dans la base de données
            $sql = "INSERT INTO photos (filename, title, description) VALUES (?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $uploaded_file, $title, $description);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "La photo a été téléchargée avec succès.";
                    $message_type = "success";
                    
                    // Rediriger vers la galerie après un court délai
                    header("Refresh: 2; URL=gallery.php");
                } else {
                    $message = "Erreur lors de l'enregistrement dans la base de données.";
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Erreur lors du téléchargement de la photo. Vérifiez que le fichier est une image valide (JPG, JPEG, PNG ou GIF).";
            $message_type = "danger";
        }
    } else {
        $message = "Veuillez sélectionner une photo à télécharger.";
        $message_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une photo - En mémoire de Pablo</title>
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
            <h1>Ajouter une photo</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form id="upload-form" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="photo-file" class="form-label">Sélectionner une photo</label>
                        <input type="file" name="photo" id="photo-file" class="form-control" accept="image/*" required>
                    </div>
                    
                    <div class="form-group">
                        <img id="photo-preview" src="#" alt="Prévisualisation" style="display: none; max-width: 100%; max-height: 300px; margin-top: 10px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Titre</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description (optionnelle)</label>
                        <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <button type="submit" name="upload" class="btn btn-primary">Télécharger</button>
                    <a href="gallery.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
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