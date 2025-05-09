<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Initialiser les variables
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si le nom d'utilisateur est vide
    if (empty(trim($_POST["username"]))) {
        $username_err = "Veuillez entrer votre nom d'utilisateur.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Vérifier si le mot de passe est vide
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Valider les identifiants
    if (empty($username_err) && empty($password_err)) {
        // Préparer une instruction select
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Lier les variables à l'instruction préparée
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Définir les paramètres
            $param_username = $username;
            
            // Tenter d'exécuter l'instruction préparée
            if (mysqli_stmt_execute($stmt)) {
                // Stocker le résultat
                mysqli_stmt_store_result($stmt);
                
                // Vérifier si le nom d'utilisateur existe, si oui, vérifier le mot de passe
                if (mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Lier les variables de résultat
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Le mot de passe est correct, démarrer une nouvelle session
                            session_start();
                            
                            // Stocker les données dans les variables de session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Rediriger l'utilisateur vers la page d'accueil
                            header("location: index.php");
                        } else {
                            // Le mot de passe n'est pas valide
                            $login_err = "Nom d'utilisateur ou mot de passe incorrect.";
                        }
                    }
                } else {
                    // Le nom d'utilisateur n'existe pas
                    $login_err = "Nom d'utilisateur ou mot de passe incorrect.";
                }
            } else {
                echo "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
            }

            // Fermer l'instruction
            mysqli_stmt_close($stmt);
        }
    }
    
    // Fermer la connexion
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - En mémoire de Pablo</title>
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
                <li class="nav-item"><a href="login.php" class="nav-link">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="form-container">
                <h2>Connexion</h2>
                <p>Veuillez remplir vos identifiants pour vous connecter.</p>

                <?php 
                if (!empty($login_err)) {
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Connexion">
                    </div>
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