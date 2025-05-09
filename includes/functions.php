<?php
// Fonctions utilitaires pour le site commémoratif de Pablo

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

// Vérifier si l'utilisateur est un administrateur
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $conn;
    $username = $_SESSION['username'];
    $sql = "SELECT * FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return ($result && mysqli_num_rows($result) === 1);
    }
    return false;
}

// Rediriger vers la page de connexion si non connecté ou non admin
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    
    // Vérifier si l'utilisateur est un administrateur
    global $conn;
    $username = $_SESSION['username'];
    $sql = "SELECT * FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) !== 1) {
            header("Location: index.php?error=unauthorized");
            exit;
        }
    } else {
        header("Location: index.php?error=system");
        exit;
    }
}

// Fonction pour uploader une photo
function uploadPhoto($file) {
    $targetDir = "uploads/photos/";
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . time() . '_' . $fileName; // Ajouter timestamp pour éviter les doublons
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Vérifier si le fichier est une image
    $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    if(in_array(strtolower($fileType), $allowTypes)){
        // Upload du fichier
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return $targetFilePath;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

// Fonction pour sécuriser les entrées
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

// Fonction pour afficher les messages d'alerte
function showAlert($message, $type = 'info') {
    echo "<div class='alert alert-{$type}' role='alert'>{$message}</div>";
}
?>